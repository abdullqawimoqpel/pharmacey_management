# Chapter 3: System Design and Implementation

## 3.1 System Architecture

### 3.1.1 High-Level Architecture Overview

This project presents an **intelligent e-commerce search and recommendation system** that operates in an end-to-end manner, combining query understanding, hybrid retrieval, advanced re-ranking, user behavior tracking, and personalization.

The system is designed as a modular pipeline architecture consisting of four main layers:

1. **Input Layer** - Receives and processes user queries and contextual data
2. **Processing Layer** - Performs retrieval, ranking, and personalization
3. **Model Layer** - Contains trained ML models for various tasks
4. **Output Layer** - Delivers ranked results and recommendations

![Figure 3.1: System Architecture](fig1_system_architecture_1765572048338.png)

*Figure 3.1: Overall System Architecture showing the flow from user input through processing layers to final output*

---

### 3.1.2 Input Sources

The system accepts data from four primary sources:

| Source | Description | Data Type |
|--------|-------------|-----------|
| **User Query** | Text search queries (may be short, contain typos, or be ambiguous) | Text |
| **User Behavior Logs** | Browsing and click data | Event logs |
| **Product Catalogs** | Product information including titles, descriptions, and attributes | Structured + Text |
| **Historical Interaction Data** | Past user-product interactions | Implicit feedback |

---

### 3.1.3 Core Components

The system comprises seven interconnected models:

1. **Semantic Encoder (SentenceTransformer)**
   - Converts queries and products into dense semantic vectors
   - Output dimension: 384

2. **BM25 Retriever**
   - Traditional lexical retrieval using TF-IDF weighting
   - Fast keyword-based matching

3. **Hybrid Retriever (RRF)**
   - Combines BM25 and Semantic retrieval using Reciprocal Rank Fusion
   - Formula: `score = Σ 1/(k + rank_i)`

4. **Pairwise Learning-to-Rank Model**
   - Neural network for relevance ranking
   - Trained on pairwise preferences

5. **DeepCAT Intent Model**
   - Predicts query intent (Brand/Category)
   - Multi-label binary classification

6. **User Embedding Module**
   - Builds user representations from behavioral history
   - Enables personalization

7. **BPR Recommender**
   - Query-free recommendation using implicit feedback
   - Bayesian Personalized Ranking optimization

![Figure 3.2: Model Architecture](fig2_model_architecture_1765572077531.png)

*Figure 3.2: Detailed architecture of Pairwise Ranker and DeepCAT Intent models*

---

## 3.2 Data Description and Preprocessing

### 3.2.1 Data Sources

| Dataset | Purpose | Size |
|---------|---------|------|
| **Amazon ESCI** | Query-Product relevance, LTR training, Intent classification | 1.8M+ examples |
| **SIGIR-ecom** | Semantic retrieval, user behavior analysis | 100K+ queries |
| **Home Depot** | Cross-domain search validation | 74K products |
| **RetailRocket** | Recommendation and user modeling | 2.7M events |

---

### 3.2.2 Data Schema

#### ESCI Dataset Schema

```
query_id     : string (unique identifier)
query        : string (user search text)
product_id   : string (ASIN)
product_title: string
product_desc : string (optional)
esci_label   : enum {E, S, C, I}
  - E = Exact match
  - S = Substitute
  - C = Complement
  - I = Irrelevant
```

#### RetailRocket Events Schema

```
visitorid : int64 (anonymous user ID)
itemid    : int64 (product ID)
event     : enum {view, addtocart, transaction}
timestamp : int64 (Unix timestamp)
```

---

### 3.2.3 Preprocessing Pipeline

![Figure 3.3: Data Pipeline](fig3_data_pipeline_1765572101707.png)

*Figure 3.3: Data preprocessing pipeline from raw sources to model-ready features*

#### Step 1: Text Cleaning
- Lowercasing all text
- Removing special characters and HTML tags
- Tokenization using standard NLP techniques
- Filtering non-English queries (US locale only)

#### Step 2: Feature Engineering
- **Document Text Construction**: `doc_text = title + " " + description + " " + attributes`
- **Query Logs**: Standardized query format
- **User Histories**: Ordered list of interacted items per user

#### Step 3: Encoding
- Text → Dense Embeddings (384-dim) using `all-MiniLM-L6-v2`
- Categorical IDs → Integer indices for embedding lookup
- User history → Averaged item embeddings

#### Step 4: Data Splitting
- ESCI: 80% train / 10% validation / 10% test
- RetailRocket: **Time-based split (leave-one-out)** to prevent data leakage

---

### 3.2.4 Data Challenges

| Challenge | Solution |
|-----------|----------|
| Missing click data in SIGIR sample | LTR deferred to ESCI dataset |
| No explicit categories in ESCI | DeepCAT uses inferred labels |
| Sparse user interactions | BPR handles implicit feedback |
| Cold start for new users | Fall back to non-personalized ranking |

---

## 3.3 Model Design

### 3.3.1 Problem Formulation

| Task | Type | Objective |
|------|------|-----------|
| Search Ranking | Learning-to-Rank | Maximize NDCG@k |
| Intent Prediction | Multi-label Classification | Predict Brand/Category |
| Recommendation | Implicit Feedback Ranking | Maximize Hit Rate |
| Personalization | User-Item Similarity | Personalize search results |

---

### 3.3.2 Model Architecture Details

#### Semantic Encoder

**Architecture:**
- Base Model: `sentence-transformers/all-MiniLM-L6-v2`
- Input: Text string (query or product)
- Output: Dense vector [1, 384]

**Justification:**
- E-commerce queries are often short and ambiguous
- Semantic similarity captures intent better than keyword matching
- Pre-trained on large corpus, transfers well to e-commerce domain

---

#### Pairwise Learning-to-Rank

**Architecture:**
```
Input: (query_emb, pos_doc_emb, neg_doc_emb) ∈ R^(3×384)

Shared Tower:
  Linear(384 → 256) → ReLU → Dropout(0.3)
  Linear(256 → 128) → ReLU → Dropout(0.3)
  Linear(128 → 1)   → Sigmoid

Output: score ∈ [0, 1]
```

**Loss Function:**
```
L = -log σ(s(q, d⁺) - s(q, d⁻))
```
where σ is the sigmoid function

**Justification:**
- Ranking is inherently a relative preference task
- Pairwise formulation naturally captures "A is better than B"
- More robust than pointwise approaches

---

#### DeepCAT Intent Model

**Architecture:**
```
Input: query_embedding ∈ R^384

Shared Layers:
  Linear(384 → 256) → ReLU → Dropout(0.2)
  Linear(256 → 128) → ReLU → Dropout(0.2)

Output Heads:
  Brand Head:    Linear(128 → 1) → Sigmoid
  Category Head: Linear(128 → 1) → Sigmoid
```

**Loss Function:**
```
L = BCE(brand_pred, brand_label) + BCE(cat_pred, cat_label)
```

**Justification:**
- E-commerce queries are often brand or category-driven
- Multi-task learning improves generalization
- Intent signals boost retrieval quality

---

#### BPR Recommender

**Architecture:**
```
User Embeddings: |U| × 64
Item Embeddings: |I| × 64

Prediction: score(u, i) = dot(user_emb[u], item_emb[i])
```

**Loss Function:**
```
L_BPR = -Σ log σ(x_ui - x_uj)
```
where i is a positive item and j is a sampled negative item

**Justification:**
- RetailRocket contains implicit feedback (no ratings)
- BPR is specifically designed for implicit feedback scenarios
- Efficient to train and inference

---

### 3.3.3 Input/Output Shapes Summary

| Model | Input Shape | Output Shape |
|-------|-------------|--------------|
| Semantic Encoder | Text string | [1, 384] |
| Pairwise Ranker | [3, 384] | [1] |
| DeepCAT | [1, 384] | [2] |
| BPR | (user_id, item_id) | [1] |

---

## 3.4 Training Pipeline and Optimization

![Figure 3.4: Training Pipeline](fig4_training_pipeline_1765572187592.png)

*Figure 3.4: Training pipeline showing loss functions, optimizers, and evaluation flow*

### 3.4.1 Training Configuration

| Parameter | Value |
|-----------|-------|
| Optimizer | Adam |
| Learning Rate | 1e-3 |
| Batch Size | 64 |
| Epochs | 10-20 |
| Hardware | Google Colab (GPU) |

---

### 3.4.2 Loss Functions Summary

| Model | Loss Function | Description |
|-------|--------------|-------------|
| Pairwise Ranker | `-log σ(s(q,d⁺) - s(q,d⁻))` | Pairwise margin loss |
| DeepCAT | Binary Cross-Entropy | Multi-label classification |
| BPR | `-log σ(x_ui - x_uj)` | Bayesian personalized ranking |

---

### 3.4.3 Regularization Techniques

1. **Dropout** (0.2-0.3) - Prevents overfitting
2. **Weight Decay** (1e-5) - L2 regularization
3. **Early Stopping** - Based on validation loss
4. **Learning Rate Scheduling** - ReduceLROnPlateau

---

## 3.5 System Implementation Details

### 3.5.1 Technology Stack

| Component | Technology |
|-----------|------------|
| Language | Python 3.10 |
| ML Framework | PyTorch 2.0 |
| Embeddings | Sentence Transformers |
| Vector Search | FAISS |
| Data Processing | pandas, numpy |
| Evaluation | scikit-learn |
| API (planned) | FastAPI |

---

### 3.5.2 Code Structure

```
src/
├── data/
│   ├── loaders.py          # Dataset loading utilities
│   ├── preprocessing.py    # Text cleaning and feature engineering
│   └── splits.py           # Train/val/test splitting
│
├── retrieval/
│   ├── bm25.py             # BM25 lexical retrieval
│   ├── semantic.py         # Dense semantic retrieval
│   └── hybrid.py           # RRF fusion
│
├── ranking/
│   ├── pairwise_ltr.py     # Learning-to-rank model
│   └── intent.py           # DeepCAT intent model
│
├── personalization/
│   ├── user_embedding.py   # User representation
│   └── personalized_search.py
│
├── recommendation/
│   └── bpr.py              # BPR recommender
│
└── api/
    ├── search.py           # POST /search endpoint
    └── recommend.py        # POST /recommend endpoint
```

---

### 3.5.3 API Endpoints

#### Search API

```python
POST /search
Request:
{
    "query": "wireless headphones",
    "user_id": "user_123",  # optional
    "top_k": 10
}

Response:
{
    "results": [
        {"product_id": "B001...", "title": "...", "score": 0.95},
        ...
    ],
    "intent": {"brand": 0.2, "category": 0.8}
}
```

#### Recommendation API

```python
POST /recommend
Request:
{
    "user_id": "user_123",
    "top_k": 10
}

Response:
{
    "recommendations": [
        {"product_id": "B002...", "title": "...", "score": 0.87},
        ...
    ]
}
```

---

## 3.6 Evaluation Methodology

![Figure 3.5: Evaluation Results](fig5_evaluation_results_1765572215651.png)

*Figure 3.5: NDCG@10 comparison across different model configurations*

### 3.6.1 Evaluation Metrics

| Task | Metric | Formula |
|------|--------|---------|
| Search | NDCG@10 | Normalized Discounted Cumulative Gain |
| Search | MRR | Mean Reciprocal Rank |
| Recommendation | Hit Rate@10 | Proportion of hits in top-10 |
| Recommendation | MRR@10 | Mean Reciprocal Rank at 10 |

---

### 3.6.2 Baselines

1. **BM25 Only** - Pure lexical retrieval
2. **Semantic Only** - Dense retrieval without fusion
3. **Hybrid (no LTR)** - RRF fusion without re-ranking
4. **Hybrid + LTR** - Full pipeline without personalization
5. **Full System** - All components enabled

---

### 3.6.3 Ablation Study Design

| Configuration | Components |
|---------------|------------|
| Baseline | BM25 |
| +Semantic | BM25 + Semantic (RRF) |
| +LTR | Above + Pairwise Ranker |
| +Intent | Above + DeepCAT boost |
| +Personal | Above + User personalization |

---

## 3.7 Deployment and Integration Plan

![Figure 3.7: Deployment Architecture](fig7_deployment_1765572264628.png)

*Figure 3.7: Three-tier deployment architecture*

### 3.7.1 Deployment Flow

```
1. Offline Training
   └── Train models on GPU cluster
   └── Save model checkpoints (.pt files)
   └── Build FAISS indices

2. Model Serving
   └── Load models into FastAPI service
   └── Initialize FAISS indices
   └── Warm-up inference pipeline

3. Production
   └── Serve requests via REST API
   └── Log user interactions
   └── Monitor performance metrics
```

---

### 3.7.2 Monitoring Metrics

| Metric | Description | Target |
|--------|-------------|--------|
| Latency (P50) | Median response time | < 100ms |
| Latency (P99) | 99th percentile | < 500ms |
| CTR | Click-through rate | > 5% |
| Conversion | Purchase rate | > 2% |

---

### 3.7.3 Retraining Strategy

- **Frequency**: Weekly batch retraining
- **Trigger**: Performance degradation or significant data drift
- **Validation**: A/B testing before full rollout

---

## 3.8 Chapter Summary

This chapter presented a comprehensive design for an intelligent e-commerce search and recommendation system. The system integrates:

- **Hybrid retrieval** combining lexical (BM25) and semantic approaches
- **Neural re-ranking** using pairwise learning-to-rank
- **Intent understanding** through DeepCAT classification
- **User personalization** based on behavioral embeddings
- **Query-free recommendations** using BPR

The modular architecture allows for incremental improvements and A/B testing of individual components. The evaluation methodology enables fair comparison against baselines and ablation analysis of each component's contribution.

In the next chapter, we present experimental results demonstrating the effectiveness of each component and the overall system performance compared to traditional approaches.

---

## Figures Reference

| Figure | Description | File |
|--------|-------------|------|
| 3.1 | System Architecture | `fig1_system_architecture_1765572048338.png` |
| 3.2 | Model Architecture | `fig2_model_architecture_1765572077531.png` |
| 3.3 | Data Pipeline | `fig3_data_pipeline_1765572101707.png` |
| 3.4 | Training Pipeline | `fig4_training_pipeline_1765572187592.png` |
| 3.5 | Evaluation Results | `fig5_evaluation_results_1765572215651.png` |
| 3.6 | Search Flow | `fig6_search_flow_1765572240499.png` |
| 3.7 | Deployment Architecture | `fig7_deployment_1765572264628.png` |
