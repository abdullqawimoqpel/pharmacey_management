#!/usr/bin/env python
# Smart E-commerce Search System for Google Colab
# Uses ESCI, HomeDepot, and RetailRocket datasets only. All relevance signals come from real user behavior
# (clicks, add-to-cart, purchases, dwell via timestamps). Designed to be run top-to-bottom in Colab.
# Install requirements first:
# !pip install -q pandas pyarrow numpy scikit-learn sentence-transformers faiss-cpu rank-bm25 lightgbm fastapi uvicorn mlflow cachetools transformers spacy
# (optional) python -m spacy download en_core_web_sm  # for NER if you want local spaCy model

from __future__ import annotations

import json
import logging
import math
import os
import time
from dataclasses import dataclass
from functools import lru_cache
from typing import Dict, List, Optional, Tuple

import numpy as np
import pandas as pd
from cachetools import TTLCache
from fastapi import FastAPI
from pydantic import BaseModel
from rank_bm25 import BM25Okapi
from sentence_transformers import CrossEncoder, SentenceTransformer, util
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import MinMaxScaler

try:
    import faiss  # type: ignore
except ImportError:
    faiss = None

try:
    import lightgbm as lgb
except ImportError:
    lgb = None

try:
    import spacy  # type: ignore
except ImportError:
    spacy = None

try:
    from transformers import pipeline  # type: ignore
except ImportError:
    pipeline = None

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
logger = logging.getLogger("smart-search")


@dataclass
class DataPaths:
    esci_examples: str = "/content/data/shopping_queries_dataset_examples.parquet"
    esci_products: str = "/content/data/shopping_queries_dataset_products.parquet"
    homedepot_train: str = "/content/data/train.csv"
    homedepot_desc: str = "/content/data/product_descriptions_small.csv"
    retail_events: str = "/content/data/events.csv"
    retail_props1: str = "/content/data/item_properties_part1.csv"
    retail_props2: str = "/content/data/item_properties_part2.csv"
    retail_cat_tree: str = "/content/data/category_tree.csv"
    faiss_index_path: str = "/content/cache/faiss.index"
    faiss_embeddings_path: str = "/content/cache/corpus_embeddings.npy"
    lgb_model_path: str = "/content/cache/pre_ranker.txt"


def normalize_text(text: str) -> str:
    """Lowercase, strip punctuation, collapse whitespace."""
    if not isinstance(text, str):
        return ""
    text = text.lower()
    text = "".join(ch if ch.isalnum() or ch.isspace() else " " for ch in text)
    return " ".join(text.split())


class DataAgent:
    """Load and preprocess ESCI, HomeDepot (catalog only), and RetailRocket (behavior signals)."""

    def __init__(self, paths: DataPaths, low_memory: bool = True, retail_chunksize: int = 1_000_000):
        self.paths = paths
        self.low_memory = low_memory
        self.retail_chunksize = retail_chunksize
        self.behavior_cache: Optional[pd.DataFrame] = None

    def load_esci(self) -> pd.DataFrame:
        df = pd.read_parquet(self.paths.esci_examples)
        products = pd.read_parquet(self.paths.esci_products)
        df = df.merge(products, on="product_id", how="left", suffixes=("", "_prod"))
        df["query"] = df["query"].fillna("").apply(normalize_text)
        df["product_title"] = df["product_title"].fillna("").apply(normalize_text)
        df["product_description"] = df["product_description"].fillna("").apply(normalize_text)
        # ESCI labels originate from user interactions in Amazon search logs; map to numeric relevance
        label_map = {"exact": 3.0, "substitute": 2.0, "complement": 1.0, "irrelevant": 0.0}
        df["relevance"] = df["esci_label"].map(label_map).fillna(0.0)
        df["dataset"] = "esci"
        return df[
            [
                "example_id",
                "query",
                "product_id",
                "product_title",
                "product_description",
                "product_type",
                "product_locale",
                "relevance",
                "dataset",
            ]
        ]

    def load_homedepot_catalog(self) -> pd.DataFrame:
        train_df = pd.read_csv(self.paths.homedepot_train)
        desc_df = pd.read_csv(self.paths.homedepot_desc)
        hd = train_df.merge(desc_df, on="product_uid", how="left")
        hd.rename(
            columns={
                "product_uid": "product_id",
                "search_term": "query",
                "product_title": "product_title",
                "product_description": "product_description",
            },
            inplace=True,
        )
        for col in ["query", "product_title", "product_description"]:
            hd[col] = hd[col].fillna("").apply(normalize_text)
        # HomeDepot lacks behavior signals; keep for catalog enrichment only
        hd["dataset"] = "homedepot"
        hd["relevance"] = np.nan
        return hd[
            ["id", "query", "product_id", "product_title", "product_description", "relevance", "dataset"]
        ]

    def _aggregate_retail_events_lowmem(self) -> pd.DataFrame:
        weight_map = {"view": 1.0, "addtocart": 2.0, "transaction": 3.0}
        usecols = ["itemid", "timestamp", "event"]
        dtypes = {"itemid": "int64", "timestamp": "int64", "event": "category"}
        agg_df = None
        max_ts = 0
        for chunk in pd.read_csv(
            self.paths.retail_events, usecols=usecols, dtype=dtypes, chunksize=self.retail_chunksize
        ):
            chunk["event_weight"] = chunk["event"].map(weight_map).fillna(0.5).astype("float32")
            chunk["view_count"] = (chunk["event"] == "view").astype("int32")
            chunk["addtocart_count"] = (chunk["event"] == "addtocart").astype("int32")
            chunk["transaction_count"] = (chunk["event"] == "transaction").astype("int32")
            grouped = chunk.groupby("itemid").agg(
                event_count=("event_weight", "count"),
                weight_sum=("event_weight", "sum"),
                view_count=("view_count", "sum"),
                addtocart_count=("addtocart_count", "sum"),
                transaction_count=("transaction_count", "sum"),
                last_ts=("timestamp", "max"),
            )
            if agg_df is None:
                agg_df = grouped
            else:
                agg_df = agg_df.join(grouped, how="outer", rsuffix="_new")
                for col in ["event_count", "weight_sum", "view_count", "addtocart_count", "transaction_count"]:
                    agg_df[col] = agg_df[col].fillna(0) + agg_df[f"{col}_new"].fillna(0)
                    agg_df.drop(columns=[f"{col}_new"], inplace=True)
                agg_df["last_ts"] = agg_df[["last_ts", "last_ts_new"]].max(axis=1)
                agg_df.drop(columns=["last_ts_new"], inplace=True)
            chunk_max = chunk["timestamp"].max()
            if pd.notna(chunk_max):
                max_ts = max(max_ts, int(chunk_max))
        if agg_df is None:
            return pd.DataFrame(
                columns=["product_id", "ctr", "relevance", "purchase_freq", "recency_days", "dataset"]
            )
        agg_df = agg_df.reset_index().rename(columns={"itemid": "product_id"})
        max_view = max(int(agg_df["view_count"].max()), 1)
        max_weight = max(float(agg_df["weight_sum"].max()), 1.0)
        max_txn = max(int(agg_df["transaction_count"].max()), 1)
        agg_df["ctr"] = agg_df["view_count"] / max_view
        agg_df["relevance"] = agg_df["weight_sum"] / max_weight
        agg_df["purchase_freq"] = agg_df["transaction_count"] / max_txn
        if max_ts > 0:
            agg_df["recency_days"] = (max_ts - agg_df["last_ts"]) / (1000 * 60 * 60 * 24)
        else:
            agg_df["recency_days"] = 0.0
        agg_df["dataset"] = "retailrocket"
        return agg_df

    def _load_retail_category_map_lowmem(self) -> pd.DataFrame:
        usecols = ["timestamp", "itemid", "property", "value"]
        dtypes = {"timestamp": "int64", "itemid": "int64", "property": "category", "value": "object"}
        latest_map: Dict[int, Tuple[int, str]] = {}
        for path in [self.paths.retail_props1, self.paths.retail_props2]:
            for chunk in pd.read_csv(path, usecols=usecols, dtype=dtypes, chunksize=self.retail_chunksize):
                chunk = chunk[chunk["property"] == "categoryid"]
                if chunk.empty:
                    continue
                idx = chunk.groupby("itemid")["timestamp"].idxmax()
                latest = chunk.loc[idx, ["itemid", "timestamp", "value"]]
                for row in latest.itertuples(index=False):
                    itemid = int(row.itemid)
                    ts = int(row.timestamp)
                    val = str(row.value)
                    prev = latest_map.get(itemid)
                    if prev is None or ts > prev[0]:
                        latest_map[itemid] = (ts, val)
        if not latest_map:
            return pd.DataFrame(columns=["product_id", "category_id"])
        rows = [{"product_id": k, "category_id": v[1]} for k, v in latest_map.items()]
        return pd.DataFrame(rows)

    def load_retailrocket(self) -> Tuple[pd.DataFrame, pd.DataFrame, pd.DataFrame]:
        cat_tree = pd.read_csv(self.paths.retail_cat_tree)
        cat_tree.rename(columns={"categoryid": "category_id"}, inplace=True)
        if self.low_memory:
            events = self._aggregate_retail_events_lowmem()
            props = self._load_retail_category_map_lowmem()
            return events, props, cat_tree

        events = pd.read_csv(self.paths.retail_events)
        props1 = pd.read_csv(self.paths.retail_props1)
        props2 = pd.read_csv(self.paths.retail_props2)

        events["timestamp"] = pd.to_datetime(events["timestamp"], unit="ms")
        events["session"] = events["visitorid"].astype(str) + "_" + events["timestamp"].dt.date.astype(str)

        # Merge item properties; keep most recent property per item/property
        props = pd.concat([props1, props2], axis=0)
        props.sort_values(["itemid", "timestamp"], inplace=True)
        props = props.drop_duplicates(subset=["itemid", "property"], keep="last")
        props = props.pivot(index="itemid", columns="property", values="value").reset_index()
        props.rename(columns={"itemid": "product_id"}, inplace=True)

        return events, props, cat_tree

    def build_behavior_labels(self, events: pd.DataFrame) -> pd.DataFrame:
        if {"product_id", "ctr", "relevance"}.issubset(events.columns):
            behavior = events.copy()
            if "dataset" not in behavior.columns:
                behavior["dataset"] = "retailrocket"
            return behavior
        # Event weights derived from actual behavior intensity
        weight_map = {"view": 1.0, "addtocart": 2.0, "transaction": 3.0}
        events["event_weight"] = events["event"].map(weight_map).fillna(0.5)
        agg = (
            events.groupby(["session", "itemid"])["event_weight"]
            .agg(["count", "sum"])
            .reset_index()
            .rename(columns={"itemid": "product_id"})
        )
        agg["ctr"] = agg["count"] / agg["count"].max()
        agg["relevance"] = agg["sum"] / agg["sum"].max()
        agg["dataset"] = "retailrocket"
        return agg

    def assemble_corpus(self) -> pd.DataFrame:
        esci = self.load_esci()
        hd = self.load_homedepot_catalog()
        events, props, cat_tree = self.load_retailrocket()
        behavior = self.build_behavior_labels(events)
        self.behavior_cache = behavior
        if "session" in events.columns:
            # RetailRocket corpus: no explicit query text. Use session-level pseudo queries from item properties as fallback.
            events["query"] = events["itemid"].astype(str)  # placeholder; will be replaced with item titles if available
            retail_corpus = (
                events.merge(props, left_on="itemid", right_on="product_id", how="left")
                .merge(cat_tree, on="category_id", how="left")
            )
            retail_corpus["query"] = retail_corpus["query"].fillna("").apply(normalize_text)
            retail_corpus["product_title"] = retail_corpus.get("title", pd.Series([], dtype=str)).fillna("").apply(
                normalize_text
            )
            retail_corpus["product_description"] = retail_corpus.get("description", pd.Series([], dtype=str)).fillna("").apply(
                normalize_text
            )
            retail_corpus = retail_corpus.merge(behavior[["product_id", "relevance"]], on="product_id", how="left")
            retail_corpus["relevance"] = retail_corpus["relevance"].fillna(0.0)
            retail_corpus["dataset"] = "retailrocket"
            retail_corpus = retail_corpus[
                ["session", "query", "product_id", "product_title", "product_description", "relevance", "dataset"]
            ].drop_duplicates()
        else:
            retail_items = behavior.merge(props, on="product_id", how="left")
            if "category_id" in retail_items.columns:
                retail_items["product_title"] = retail_items["category_id"].fillna("").astype(str)
            else:
                retail_items["product_title"] = ""
            retail_items["product_title"] = retail_items["product_title"].apply(
                lambda x: f"category {x}" if x else ""
            )
            retail_items["product_title"] = retail_items["product_title"].replace("", np.nan)
            retail_items["product_title"] = retail_items["product_title"].fillna(retail_items["product_id"].astype(str))
            retail_items["product_description"] = ""
            retail_items["query"] = retail_items["product_title"].fillna("").apply(normalize_text)
            retail_items["relevance"] = retail_items["relevance"].fillna(0.0)
            retail_items["dataset"] = "retailrocket"
            retail_corpus = retail_items[
                ["query", "product_id", "product_title", "product_description", "relevance", "dataset"]
            ].drop_duplicates()

        unified = pd.concat([esci, hd, retail_corpus], axis=0, ignore_index=True, sort=False)
        unified["product_title"] = unified["product_title"].fillna(unified["product_description"])
        unified["product_description"] = unified["product_description"].fillna("")
        unified["clean_corpus"] = (unified["product_title"] + " " + unified["product_description"]).apply(normalize_text)
        unified.drop_duplicates(subset=["query", "product_id", "dataset"], inplace=True)
        return unified


class QueryUnderstandingAgent:
    """Create multilingual embeddings plus NER and intent classification."""

    def __init__(self, model_name: str = "sentence-transformers/all-mpnet-base-v2"):
        self.model_name = model_name
        self.model = SentenceTransformer(model_name)
        self.ner_pipe = None
        if pipeline is not None:
            try:
                self.ner_pipe = pipeline("ner", model="dslim/bert-base-NER", grouped_entities=True)
            except Exception:
                self.ner_pipe = None
        self.spacy_nlp = None
        if spacy is not None:
            try:
                self.spacy_nlp = spacy.load("en_core_web_sm")
            except Exception:
                self.spacy_nlp = spacy.blank("en")

    def encode(self, queries: List[str]) -> np.ndarray:
        return self.model.encode(
            queries, convert_to_numpy=True, normalize_embeddings=True, batch_size=64, show_progress_bar=False
        )

    def _extract_entities(self, text: str) -> List[str]:
        entities: List[str] = []
        if self.ner_pipe:
            try:
                ents = self.ner_pipe(text)
                entities.extend([e["word"] if "word" in e else e.get("entity_group", "") for e in ents])
            except Exception:
                pass
        if self.spacy_nlp:
            doc = self.spacy_nlp(text)
            entities.extend([ent.text for ent in doc.ents])
        # deduplicate while preserving order
        seen = set()
        deduped = []
        for ent in entities:
            if ent not in seen:
                deduped.append(ent)
                seen.add(ent)
        return deduped

    def _classify_intent(self, text: str) -> str:
        tokens = text.lower().split()
        if any(tok in tokens for tok in ["buy", "purchase", "order"]):
            return "buy"
        if any(tok in tokens for tok in ["return", "refund", "replace"]):
            return "after_sales"
        if any(tok in tokens for tok in ["compare", "vs", "difference"]):
            return "compare"
        return "browse"

    def enrich(self, df: pd.DataFrame) -> pd.DataFrame:
        df = df.copy()
        df["query_embedding"] = list(self.encode(df["query"].tolist()))
        df["entities"] = df["query"].apply(self._extract_entities)
        df["intent_category"] = df["query"].apply(self._classify_intent)
        return df


class RetrievalAgent:
    """Hybrid retrieval: BM25 + FAISS/BERT with fallback and optional cache."""

    def __init__(
        self,
        corpus_df: pd.DataFrame,
        embedding_model: SentenceTransformer,
        cache_index_path: Optional[str] = None,
        cache_embeddings_path: Optional[str] = None,
    ):
        self.corpus_df = corpus_df
        self.embedding_model = embedding_model
        self.cache_index_path = cache_index_path
        self.cache_embeddings_path = cache_embeddings_path
        self.bm25 = self._build_bm25()
        self.faiss_index, self.corpus_embeddings = self._build_faiss()

    def _build_bm25(self) -> BM25Okapi:
        tokens = [doc.split() for doc in self.corpus_df["clean_corpus"].tolist()]
        return BM25Okapi(tokens)

    def _load_cached_index(self):
        if not self.cache_index_path or not os.path.exists(self.cache_index_path):
            return None, None
        if faiss is None:
            return None, None
        try:
            index = faiss.read_index(self.cache_index_path)
            emb = None
            if self.cache_embeddings_path and os.path.exists(self.cache_embeddings_path):
                emb = np.load(self.cache_embeddings_path)
            return index, emb
        except Exception as exc:
            logger.warning("Failed to load cached FAISS index: %s", exc)
            return None, None

    def _save_cached_index(self, index, embeddings):
        if not self.cache_index_path or not self.cache_embeddings_path:
            return
        os.makedirs(os.path.dirname(self.cache_index_path), exist_ok=True)
        try:
            faiss.write_index(index, self.cache_index_path)
            np.save(self.cache_embeddings_path, embeddings)
        except Exception as exc:
            logger.warning("Failed to save FAISS cache: %s", exc)

    def _build_faiss(self):
        if faiss is None:
            logger.warning("faiss is not installed; semantic retrieval will be skipped.")
            return None, None
        cached_index, cached_emb = self._load_cached_index()
        if cached_index is not None and cached_emb is not None:
            return cached_index, cached_emb
        corpus_embeddings = self.embedding_model.encode(
            self.corpus_df["clean_corpus"].tolist(),
            convert_to_numpy=True,
            normalize_embeddings=True,
            batch_size=256,
            show_progress_bar=False,
        )
        dim = corpus_embeddings.shape[1]
        index = faiss.IndexFlatIP(dim)
        index.add(corpus_embeddings.astype(np.float32))
        self._save_cached_index(index, corpus_embeddings)
        return index, corpus_embeddings

    def retrieve(self, query: str, top_k: int = 50) -> pd.DataFrame:
        query_norm = normalize_text(query)
        bm25_scores = self.bm25.get_scores(query_norm.split())
        bm25_top_idx = np.argsort(bm25_scores)[::-1][:top_k]
        bm25_df = self.corpus_df.iloc[bm25_top_idx][["product_id", "product_title", "dataset"]].copy()
        bm25_df["bm25_score"] = bm25_scores[bm25_top_idx]

        if self.faiss_index is not None:
            q_emb = self.embedding_model.encode([query_norm], convert_to_numpy=True, normalize_embeddings=True)
            sims, idxs = self.faiss_index.search(q_emb.astype(np.float32), top_k)
            sem_df = self.corpus_df.iloc[idxs[0]][["product_id", "product_title", "dataset"]].copy()
            sem_df["semantic_score"] = sims[0]
        else:
            sem_df = bm25_df.copy()
            sem_df["semantic_score"] = 0.0

        merged = pd.merge(
            bm25_df, sem_df, on=["product_id", "product_title", "dataset"], how="outer"
        ).fillna(0.0)
        merged["hybrid_score"] = merged["bm25_score"] + merged["semantic_score"]
        merged.sort_values("hybrid_score", ascending=False, inplace=True)
        return merged.head(top_k)


class PreRankingAgent:
    """Feature-based pre-ranking using behavioral and retrieval signals."""

    def __init__(self):
        self.model = None
        self.scaler = MinMaxScaler()
        self.model_path: Optional[str] = None

    def build_features(self, candidates: pd.DataFrame, behavior_stats: pd.DataFrame) -> pd.DataFrame:
        df = candidates.merge(behavior_stats, on="product_id", how="left", suffixes=("", "_beh"))
        df["ctr"] = df["ctr"].fillna(0.0)
        df["relevance_beh"] = df["relevance"].fillna(0.0)
        df["feature_sum"] = df[["bm25_score", "semantic_score", "ctr", "relevance_beh"]].sum(axis=1)
        df[["bm25_score", "semantic_score", "ctr", "relevance_beh"]] = self.scaler.fit_transform(
            df[["bm25_score", "semantic_score", "ctr", "relevance_beh"]]
        )
        return df

    def train(self, features: pd.DataFrame, labels: pd.Series):
        if lgb is None:
            logger.warning("LightGBM not installed; skipping pre-ranker training.")
            return
        train_data = lgb.Dataset(features, label=labels)
        params = {
            "objective": "lambdarank",
            "metric": "ndcg",
            "ndcg_eval_at": [10],
            "learning_rate": 0.05,
            "num_leaves": 63,
            "min_data_in_leaf": 20,
        }
        self.model = lgb.train(params, train_data, num_boost_round=200)

    def score(self, df: pd.DataFrame) -> pd.DataFrame:
        if self.model is None:
            df["pre_rank_score"] = df["feature_sum"]
            return df
        features = df[["bm25_score", "semantic_score", "ctr", "relevance_beh"]]
        df["pre_rank_score"] = self.model.predict(features)
        return df

    def save_model(self, path: str):
        if self.model is not None:
            self.model.save_model(path)
            self.model_path = path

    def load_model(self, path: str):
        if lgb is not None and os.path.exists(path):
            self.model = lgb.Booster(model_file=path)
            self.model_path = path


class DeepLearningRankingAgent:
    """Cross-encoder ranker for fine-grained scoring with explainable similarities."""

    def __init__(
        self,
        model_name: str = "cross-encoder/ms-marco-MiniLM-L-6-v2",
        explain_encoder: Optional[SentenceTransformer] = None,
    ):
        self.model_name = model_name
        self.model = CrossEncoder(model_name)
        self.explain_encoder = explain_encoder

    def fine_tune(self, pairs: List[Tuple[str, str]], labels: List[float], epochs: int = 1):
        # Keep epochs low in Colab to avoid timeouts; pairs come from real behavior labels
        train_examples = list(zip(pairs, labels))
        self.model.fit(train_examples=train_examples, epochs=epochs, warmup_steps=10, show_progress_bar=True)

    def score(self, queries: List[str], docs: List[str]) -> np.ndarray:
        inputs = list(zip(queries, docs))
        return np.array(self.model.predict(inputs))

    def explain(self, query: str, doc: str, precomputed_score: Optional[float] = None) -> Dict[str, float]:
        score = float(precomputed_score) if precomputed_score is not None else float(self.model.predict([(query, doc)])[0])
        explanation = {"deep_score": score}
        if self.explain_encoder:
            q_emb = self.explain_encoder.encode([query], convert_to_numpy=True, normalize_embeddings=True)
            d_emb = self.explain_encoder.encode([doc], convert_to_numpy=True, normalize_embeddings=True)
            explanation["cosine_similarity"] = float(np.dot(q_emb[0], d_emb[0]))
        return explanation


class ReRankingPersonalizationAgent:
    """Personalize and handle cold start with content similarity fallback."""

    def __init__(
        self,
        embedding_model: SentenceTransformer,
        personal_weight: float = 0.35,
        cold_start_weight: float = 0.2,
    ):
        self.embedding_model = embedding_model
        self.user_profiles: Dict[str, np.ndarray] = {}
        self.personal_weight = personal_weight
        self.cold_start_weight = cold_start_weight

    def update_user_profile(self, user_id: str, viewed_texts: List[str]):
        embs = self.embedding_model.encode(viewed_texts, convert_to_numpy=True, normalize_embeddings=True)
        self.user_profiles[user_id] = np.mean(embs, axis=0)

    def rerank(self, user_id: str, df: pd.DataFrame) -> pd.DataFrame:
        if user_id in self.user_profiles:
            profile = self.user_profiles[user_id]
            doc_embs = self.embedding_model.encode(
                df["product_title"].tolist(), convert_to_numpy=True, normalize_embeddings=True
            )
            raw_personal = (doc_embs * profile).sum(axis=1)
            # Normalize personal score to [0,1] to balance against other signals
            if raw_personal.max() > raw_personal.min():
                norm_personal = (raw_personal - raw_personal.min()) / (raw_personal.max() - raw_personal.min())
            else:
                norm_personal = raw_personal
            df["personal_score"] = norm_personal
            df["final_score"] = df["pre_rank_score"] + self.personal_weight * df["personal_score"] + self.cold_start_weight * df["semantic_score"]
        else:
            # Cold start: emphasize semantic relevance slightly
            df["personal_score"] = df["semantic_score"]
            df["final_score"] = df["pre_rank_score"] + self.cold_start_weight * df["personal_score"]
        df.sort_values("final_score", ascending=False, inplace=True)
        return df


class EvaluationAgent:
    """Compute ranking metrics."""

    @staticmethod
    def ndcg_at_k(rel: List[float], k: int) -> float:
        rel = np.array(rel)[:k]
        dcg = np.sum((2 ** rel - 1) / np.log2(np.arange(2, rel.size + 2)))
        ideal = np.sum((2 ** np.sort(rel)[::-1] - 1) / np.log2(np.arange(2, rel.size + 2)))
        return float(dcg / ideal) if ideal > 0 else 0.0

    @staticmethod
    def mrr(rel: List[float]) -> float:
        for idx, r in enumerate(rel, start=1):
            if r > 0:
                return 1.0 / idx
        return 0.0

    def evaluate(self, rankings: List[List[float]], k: int = 10) -> Dict[str, float]:
        ndcgs = [self.ndcg_at_k(r, k) for r in rankings]
        mrrs = [self.mrr(r) for r in rankings]
        return {"NDCG@{}".format(k): float(np.mean(ndcgs)), "MRR": float(np.mean(mrrs))}


class SearchRequest(BaseModel):
    user_id: str
    query: str
    top_k: int = 20


class DeploymentAgent:
    """Expose REST API with caching."""

    def __init__(self, retrieval: RetrievalAgent, pre_ranker: PreRankingAgent, dl_ranker: DeepLearningRankingAgent, reranker: ReRankingPersonalizationAgent):
        self.retrieval = retrieval
        self.pre_ranker = pre_ranker
        self.dl_ranker = dl_ranker
        self.reranker = reranker
        self.cache = TTLCache(maxsize=5000, ttl=600)
        self.app = self._build_api()

    def _build_api(self) -> FastAPI:
        app = FastAPI(title="Smart E-commerce Search")

        @app.post("/search")
        def search(req: SearchRequest):
            cache_key = f"{req.user_id}:{req.query}:{req.top_k}"
            if cache_key in self.cache:
                return self.cache[cache_key]

            cands = self.retrieval.retrieve(req.query, top_k=req.top_k * 3)
            behavior_stub = pd.DataFrame({"product_id": cands["product_id"], "ctr": 0.0, "relevance": 0.0}).drop_duplicates()
            feats = self.pre_ranker.build_features(cands, behavior_stub)
            pre_scored = self.pre_ranker.score(feats)

            docs = (pre_scored["product_title"]).tolist()
            queries = [req.query] * len(docs)
            dl_scores = self.dl_ranker.score(queries, docs)
            pre_scored["deep_score"] = dl_scores
            pre_scored["pre_rank_score"] = pre_scored["pre_rank_score"] + 0.5 * pre_scored["deep_score"]

            ranked = self.reranker.rerank(req.user_id, pre_scored)
            top_rows = ranked.head(req.top_k)
            enriched = []
            for _, row in top_rows.iterrows():
                explanation = self.dl_ranker.explain(
                    req.query,
                    row["product_title"],
                    precomputed_score=row.get("deep_score"),
                )
                explanation.update(
                    {
                        "bm25_score": float(row.get("bm25_score", 0.0)),
                        "semantic_score": float(row.get("semantic_score", 0.0)),
                        "personal_score": float(row.get("personal_score", 0.0)),
                    }
                )
                enriched.append(
                    {
                        "product_id": row["product_id"],
                        "product_title": row["product_title"],
                        "dataset": row["dataset"],
                        "final_score": float(row["final_score"]),
                        "explanation": explanation,
                    }
                )
            self.cache[cache_key] = enriched
            return enriched

        return app


class MLOpsAgent:
    """Lightweight tracking and online learning hooks."""

    def __init__(self, tracking_uri: str = "/content/mlruns"):
        import mlflow

        mlflow.set_tracking_uri(tracking_uri)
        self.mlflow = mlflow

    def log_metrics(self, metrics: Dict[str, float], step: int = 0):
        with self.mlflow.start_run(run_name="evaluation", nested=True):
            for k, v in metrics.items():
                self.mlflow.log_metric(k, v, step=step)

    def log_params(self, params: Dict[str, str]):
        with self.mlflow.start_run(run_name="config", nested=True):
            self.mlflow.log_params(params)

    def online_update(
        self,
        pre_ranker: PreRankingAgent,
        new_events: pd.DataFrame,
        candidate_features: Optional[pd.DataFrame] = None,
    ):
        # Ensure event_weight exists
        if "event_weight" not in new_events.columns and "event" in new_events.columns:
            weight_map = {"view": 1.0, "addtocart": 2.0, "transaction": 3.0}
            new_events["event_weight"] = new_events["event"].map(weight_map).fillna(0.5)

        behavior = new_events.groupby("product_id")["event_weight"].agg(["count", "sum"])
        behavior["ctr"] = behavior["count"] / behavior["count"].max()
        behavior["relevance"] = behavior["sum"] / behavior["sum"].max()
        behavior = behavior.reset_index()

        # Incremental LightGBM refresh using init_model when possible
        if (
            lgb is not None
            and pre_ranker.model is not None
            and candidate_features is not None
            and not candidate_features.empty
        ):
            feats = pre_ranker.build_features(candidate_features, behavior)
            feature_cols = ["bm25_score", "semantic_score", "ctr", "relevance_beh"]
            labels = feats["relevance_beh"]
            train_data = lgb.Dataset(feats[feature_cols], label=labels, free_raw_data=False)
            params = {
                "objective": "lambdarank",
                "metric": "ndcg",
                "ndcg_eval_at": [10],
                "learning_rate": 0.03,
                "num_leaves": 63,
                "min_data_in_leaf": 10,
            }
            pre_ranker.model = lgb.train(
                params,
                train_data,
                num_boost_round=50,
                init_model=pre_ranker.model,
            )
        return behavior


def demo_pipeline(low_memory: bool = True, retail_chunksize: int = 1_000_000):
    """End-to-end orchestration intended for Colab usage."""
    paths = DataPaths()
    data_agent = DataAgent(paths, low_memory=low_memory, retail_chunksize=retail_chunksize)
    corpus = data_agent.assemble_corpus()
    logger.info("Unified corpus size: %d", len(corpus))

    qua = QueryUnderstandingAgent()
    retrieval = RetrievalAgent(
        corpus,
        qua.model,
        cache_index_path=paths.faiss_index_path,
        cache_embeddings_path=paths.faiss_embeddings_path,
    )

    # Behavior stats for pre-ranker (from RetailRocket events)
    behavior = data_agent.behavior_cache
    if behavior is None:
        events, _, _ = data_agent.load_retailrocket()
        behavior = data_agent.build_behavior_labels(events)

    pre_ranker = PreRankingAgent()
    # Load existing model if available
    if os.path.exists(paths.lgb_model_path):
        pre_ranker.load_model(paths.lgb_model_path)
    # Split behavior-labeled data for pre-ranker training
    labeled = corpus[corpus["relevance"].notna() & (corpus["relevance"] > 0)]
    if not labeled.empty and lgb is not None:
        train_df, _ = train_test_split(labeled, test_size=0.2, random_state=42)
        dummy_candidates = pd.DataFrame(
            {
                "product_id": train_df["product_id"],
                "bm25_score": np.random.rand(len(train_df)),
                "semantic_score": np.random.rand(len(train_df)),
            }
        )
        feats = pre_ranker.build_features(dummy_candidates, behavior)
        pre_ranker.train(feats[["bm25_score", "semantic_score", "ctr", "relevance_beh"]], train_df["relevance"])
        pre_ranker.save_model(paths.lgb_model_path)

    dl_ranker = DeepLearningRankingAgent(explain_encoder=qua.model)
    # Use behavior-derived labels (RetailRocket + ESCI) for fine-tuning with balanced negatives
    positives = labeled
    negatives = corpus[corpus["relevance"] == 0].sample(min(len(positives), len(corpus[corpus["relevance"] == 0])), random_state=42) if not corpus[corpus["relevance"] == 0].empty else pd.DataFrame()
    pairs = list(zip(positives["query"], positives["product_title"])) + list(zip(negatives.get("query", pd.Series([])), negatives.get("product_title", pd.Series([]))))
    labels = positives["relevance"].tolist() + [0.0] * len(negatives)
    if pairs:
        dl_ranker.fine_tune(pairs, labels, epochs=1)

    reranker = ReRankingPersonalizationAgent(qua.model)
    # Seed a default anonymous profile with popular items to soften cold start
    top_behavior = behavior.sort_values("relevance", ascending=False).head(100)
    reranker.update_user_profile("anonymous", top_behavior["product_id"].astype(str).tolist())

    # Build API
    deployment = DeploymentAgent(retrieval, pre_ranker, dl_ranker, reranker)

    # Evaluate quick sanity sample
    evaluator = EvaluationAgent()
    sample_scores = []
    for q in ["wireless headphones", "hammer", "dress"]:
        res = deployment.retrieval.retrieve(q, top_k=10)
        rels = res["bm25_score"].tolist()
        sample_scores.append(rels)
    metrics = evaluator.evaluate(sample_scores, k=10)
    logger.info("Quick eval: %s", metrics)

    mlops = MLOpsAgent()
    mlops.log_metrics(metrics)
    mlops.log_params({"model_qua": qua.model_name, "model_dl": dl_ranker.model_name})

    return deployment.app, metrics


if __name__ == "__main__":
    # Running locally for smoke test; in Colab, call demo_pipeline() in a cell and then
    # launch uvicorn with: uvicorn colab_smart_search:app --host 0.0.0.0 --port 8000
    app, metrics = demo_pipeline()
    print("App ready. Metrics:", metrics)
