# Chapter 3: System Design and Implementation

## 3.1 System Architecture

### 3.1.1 Architectural Overview

The proposed system is a **hybrid e-commerce search and recommendation framework** that integrates Semantic Dense Retrieval with Learning-to-Rank (LTR) for adaptive relevance scoring. The architecture is designed to address two primary challenges in e-commerce search: the vocabulary mismatch problem between user queries and product descriptions, and the need for personalized ranking based on implicit user feedback.

To achieve this, the system employs a **"Retrieve-then-Rank"** pipeline. Unlike traditional keyword-based approaches that rely solely on lexical matching, our architecture first performs hybrid retrieval combining BM25 (sparse) and Sentence Transformer embeddings (dense), then applies a neural re-ranker trained on pairwise relevance labels. The retrieval results are further enhanced by a DeepCAT intent classification module and personalized using user behavioral embeddings.

The core innovation lies in the fusion of multiple retrieval signals through **Reciprocal Rank Fusion (RRF)**, combined with a **Pairwise Learning-to-Rank** model that learns relative preferences rather than absolute relevance scores. This design enables the system to capture both lexical precision and semantic understanding while adapting to individual user preferences.

---

### 3.1.2 End-to-End Data Flow

The system operation follows a strictly defined pipeline, as illustrated in Figure 3.1:

1. **Query Ingestion**: The process begins with a raw user query string (e.g., "wireless noise cancelling headphones") and optional user context (User ID, session history).

2. **Query Processing Module**:
   - The raw query undergoes text normalization (lowercasing, special character removal).
   - Query embedding is generated using a pre-trained Sentence Transformer model.
   - DeepCAT Intent Model predicts Brand/Category intent scores.

3. **Hybrid Retrieval Layer**:
   - **BM25 Retriever**: Performs lexical matching using TF-IDF weighted term frequency.
   - **Semantic Dense Retriever**: Uses FAISS to find nearest neighbors in the embedding space.
   - **RRF Fusion**: Combines both ranked lists using Reciprocal Rank Fusion.
   - Output: Top-K candidate products (typically K=100).

4. **Re-Ranking Layer (Pairwise LTR)**:
   - The candidate products are scored by a neural network trained on pairwise preferences.
   - Query-document similarity features are computed.
   - Intent boost is applied based on DeepCAT predictions.
   - Output: Re-ranked list with refined relevance scores.

5. **Personalization Layer**:
   - User embedding is retrieved from the User Embedding Module.
   - Personalization score is computed as cosine similarity between user and product embeddings.
   - Final score is a weighted combination: `score_final = α × score_LTR + (1-α) × score_personal`

6. **Output & Feedback Loop**:
   - Top-N results are returned to the user (N=10 typically).
   - User interactions (clicks, purchases) are logged.
   - Feedback is used to update user embeddings and retrain models periodically.

---

### 3.1.3 Key Technical Components

| Component | Role | Technology |
|-----------|------|------------|
| **Sentence Transformer Encoder** | Maps text to 384-dim dense vectors | `all-MiniLM-L6-v2` |
| **BM25 Retriever** | Lexical sparse retrieval | Rank-BM25 library |
| **FAISS Index** | Approximate nearest neighbor search | Facebook FAISS |
| **Pairwise LTR Model** | Neural re-ranking based on relative preferences | PyTorch MLP |
| **DeepCAT Intent Model** | Multi-label intent classification | PyTorch (Multi-head) |
| **User Embedding Module** | Behavioral user representation | Weighted average pooling |
| **BPR Recommender** | Query-free collaborative filtering | Bayesian Personalized Ranking |

---

## 3.2 Data Description and Preprocessing

### 3.2.1 Data Sources

The system is built and evaluated using four complementary datasets that together provide a comprehensive view of e-commerce user behavior and product relevance:

| Dataset | Source | Primary Use Case |
|---------|--------|------------------|
| **Amazon ESCI** | Amazon (KDD Cup 2022) | Query-Product relevance, LTR training |
| **SIGIR-ecom** | SIGIR e-commerce workshop | Semantic retrieval validation |
| **Home Depot** | Kaggle competition | Cross-domain generalization |
| **RetailRocket** | Kaggle (Russian e-commerce) | User behavior modeling, BPR training |

The Amazon ESCI dataset is selected as the primary training source due to its explicit relevance labels (Exact, Substitute, Complement, Irrelevant), which enable supervised training of the ranking models. RetailRocket provides implicit feedback (clicks, cart additions, purchases) essential for personalization and recommendation modules.

---

### 3.2.2 Data Schema and Statistics

#### Amazon ESCI Dataset Schema

| Field | Type | Description |
|-------|------|-------------|
| `query_id` | string | Unique query identifier |
| `query` | string | User search query text |
| `product_id` | string | Amazon ASIN |
| `product_title` | string | Product title |
| `product_description` | string | Product description (optional) |
| `product_bullet_point` | string | Key product features |
| `product_brand` | string | Brand name |
| `product_color` | string | Color attribute |
| `esci_label` | enum | Relevance label ∈ {E, S, C, I} |

**Relevance Label Distribution:**

| Label | Meaning | Count | Percentage |
|-------|---------|-------|------------|
| E (Exact) | Perfect match | 482,139 | 26.8% |
| S (Substitute) | Acceptable alternative | 315,427 | 17.5% |
| C (Complement) | Related but not substitute | 198,654 | 11.0% |
| I (Irrelevant) | Not useful | 804,283 | 44.7% |
| **Total** | | **1,800,503** | **100%** |

The distribution exhibits the characteristic imbalance of e-commerce data: Irrelevant items dominate at 44.7%, while high-value Exact matches represent only 26.8%.

#### RetailRocket Events Schema

| Field | Type | Description |
|-------|------|-------------|
| `visitorid` | int64 | Anonymous user identifier |
| `itemid` | int64 | Product identifier |
| `event` | enum | Interaction type ∈ {view, addtocart, transaction} |
| `timestamp` | int64 | Unix timestamp of interaction |

**Event Distribution:**

| Event Type | Count | Percentage | Weight |
|------------|-------|------------|--------|
| View | 2,664,312 | 98.6% | 1.0 |
| Add to Cart | 69,332 | 2.6% | 3.0 |
| Transaction | 22,457 | 0.8% | 10.0 |
| **Total** | **2,756,101** | **100%** | - |

---

### 3.2.3 Preprocessing Pipeline

The raw data is passed through a well-defined transformation pipeline to prepare it for the Hybrid Retrieval + LTR architecture:

#### Step 1: Text Normalization

All textual fields undergo standardized preprocessing:

```python
def normalize_text(text):
    text = text.lower()                    # Lowercase
    text = re.sub(r'[^\w\s]', ' ', text)   # Remove punctuation
    text = re.sub(r'\s+', ' ', text)       # Collapse whitespace
    return text.strip()
```

#### Step 2: Document Text Construction

For each product, we construct a unified document text by concatenating available fields:

```
doc_text = title + " " + brand + " " + bullet_points + " " + description
```

This concatenation ensures that all product information is available for both lexical and semantic matching.

#### Step 3: Embedding Generation

We use the `sentence-transformers/all-MiniLM-L6-v2` model to generate dense embeddings:

- **Query Embedding**: `e_q ∈ ℝ^384`
- **Product Embedding**: `e_p ∈ ℝ^384`

All embeddings are L2-normalized to enable efficient cosine similarity computation via dot product.

#### Step 4: FAISS Index Construction

Product embeddings are indexed using FAISS for efficient approximate nearest neighbor search:

```python
index = faiss.IndexFlatIP(384)  # Inner product for cosine similarity
index.add(product_embeddings)   # Add all product vectors
```

#### Step 5: Chronological Splitting (Anti-Leakage Strategy)

Unlike standard classification settings, a random 80/20 split is not acceptable because it would break the temporal order of user behavior—predicting past events from future interactions constitutes data leakage.

**For ESCI Dataset:**
- Train: 80% of query-product pairs
- Validation: 10%
- Test: 10%

**For RetailRocket (Time-based Split):**
- For each user, interactions are sorted by timestamp.
- Train: First 80% of each user's interactions.
- Test: Remaining 20%.

This design strictly mimics a real-world scenario in which the model must predict future interests using only historical context.

---

### 3.2.4 Handling Data Challenges

| Challenge | Impact | Solution |
|-----------|--------|----------|
| **Label Imbalance** | Model biased toward "Irrelevant" | Weighted sampling, class weights in loss |
| **Missing Descriptions** | ~30% products lack descriptions | Use title + bullets as fallback |
| **Cold Start (New Users)** | No history for personalization | Fall back to non-personalized ranking |
| **Vocabulary Mismatch** | Query terms ≠ product terms | Semantic retrieval bridges the gap |

---

## 3.3 Model Design

This section presents the mathematical formulation and architectural decisions behind the proposed system. The model follows a hybrid design that separates retrieval from ranking, bridging the gap between recall-oriented candidate generation and precision-oriented relevance scoring.

---

### 3.3.1 Semantic Encoder (Sentence Transformer)

The foundation of semantic understanding is a pre-trained Sentence Transformer model that maps text sequences to dense vector representations.

#### Model Selection

We use the `all-MiniLM-L6-v2` model, selected for its balance between quality and efficiency:

| Property | Value |
|----------|-------|
| Architecture | BERT-based (6 layers) |
| Output Dimension | 384 |
| Max Sequence Length | 256 tokens |
| Inference Speed | ~14,000 sentences/sec (GPU) |

#### Encoding Process

For a text sequence $x = [x_1, x_2, ..., x_n]$, the encoder produces:

$$e = \text{MeanPooling}(\text{BERT}(x))$$

where MeanPooling averages the token embeddings from the final layer, and the output is L2-normalized:

$$\hat{e} = \frac{e}{\|e\|_2}$$

#### Similarity Computation

For query embedding $e_q$ and product embedding $e_p$, semantic similarity is computed as:

$$\text{sim}(q, p) = e_q^\top e_p = \cos(e_q, e_p)$$

Due to L2-normalization, the dot product equals cosine similarity.

---

### 3.3.2 Hybrid Retrieval with Reciprocal Rank Fusion

The retrieval stage combines lexical and semantic signals to maximize recall while maintaining precision.

#### BM25 Retrieval (Sparse)

BM25 scores documents based on term frequency and inverse document frequency:

$$\text{BM25}(q, d) = \sum_{t \in q} \text{IDF}(t) \cdot \frac{f(t, d) \cdot (k_1 + 1)}{f(t, d) + k_1 \cdot (1 - b + b \cdot \frac{|d|}{\text{avgdl}})}$$

where:
- $f(t, d)$ = frequency of term $t$ in document $d$
- $|d|$ = document length
- $\text{avgdl}$ = average document length
- $k_1 = 1.5$, $b = 0.75$ (standard parameters)

#### Semantic Retrieval (Dense)

Semantic retrieval uses FAISS to find the top-K products with highest embedding similarity:

$$\text{TopK}_{\text{semantic}}(q) = \underset{p \in P}{\text{argmax-K}} \; e_q^\top e_p$$

#### Reciprocal Rank Fusion (RRF)

RRF combines the two ranked lists by assigning scores based on rank position rather than raw scores:

$$\text{RRF}(p) = \sum_{r \in \{BM25, Semantic\}} \frac{1}{k + \text{rank}_r(p)}$$

where $k = 60$ is a constant that dampens the effect of high ranks.

**Rationale:** RRF is robust to score distribution differences between retrievers and requires no tuning of fusion weights.

---

### 3.3.3 Pairwise Learning-to-Rank Model

The re-ranking stage uses a neural network trained on pairwise preferences to refine the candidate ordering.

#### Problem Formulation

Given a query $q$ and two candidate products $d^+$ (more relevant) and $d^-$ (less relevant), the model learns to assign a higher score to $d^+$:

$$P(d^+ \succ d^- | q) = \sigma(s(q, d^+) - s(q, d^-))$$

where $\sigma$ is the sigmoid function and $s(q, d)$ is the scoring function.

#### Network Architecture

The scoring function is implemented as a Multi-Layer Perceptron:

```
Input: [query_emb ⊕ doc_emb ⊕ interaction_features] ∈ ℝ^(384+384+k)

Architecture:
  Linear(768+k → 256) → ReLU → Dropout(0.3)
  Linear(256 → 128)   → ReLU → Dropout(0.3)
  Linear(128 → 64)    → ReLU
  Linear(64 → 1)      → Sigmoid

Output: relevance_score ∈ [0, 1]
```

#### Interaction Features

Beyond raw embeddings, we compute explicit interaction features:

| Feature | Formula | Dimension |
|---------|---------|-----------|
| Cosine Similarity | $\cos(e_q, e_d)$ | 1 |
| Element-wise Product | $e_q \odot e_d$ | 384 |
| Element-wise Difference | $|e_q - e_d|$ | 384 |

Total feature dimension: $384 + 384 + 384 + 384 + 1 = 1,537$

#### Loss Function

The pairwise ranking loss (also known as RankNet loss) is:

$$\mathcal{L}_{\text{rank}} = -\sum_{(q, d^+, d^-)} \log \sigma(s(q, d^+) - s(q, d^-))$$

This loss encourages the model to assign higher scores to more relevant documents.

---

### 3.3.4 DeepCAT Intent Classification Model

E-commerce queries often express implicit intent toward specific brands or product categories. The DeepCAT model captures this intent to boost retrieval quality.

#### Architecture

```
Input: query_embedding ∈ ℝ^384

Shared Layers:
  Linear(384 → 256) → ReLU → BatchNorm → Dropout(0.2)
  Linear(256 → 128) → ReLU → BatchNorm → Dropout(0.2)

Output Heads:
  Brand Head:    Linear(128 → 1) → Sigmoid → brand_score ∈ [0, 1]
  Category Head: Linear(128 → 1) → Sigmoid → category_score ∈ [0, 1]
```

#### Loss Function

Multi-task Binary Cross-Entropy:

$$\mathcal{L}_{\text{intent}} = \text{BCE}(\hat{y}_{\text{brand}}, y_{\text{brand}}) + \text{BCE}(\hat{y}_{\text{cat}}, y_{\text{cat}})$$

#### Intent Boost Integration

Intent scores are used to boost relevant products during ranking:

$$s_{\text{boosted}}(q, d) = s(q, d) \times (1 + \beta_b \cdot I_{\text{brand}} \cdot M_{\text{brand}} + \beta_c \cdot I_{\text{cat}} \cdot M_{\text{cat}})$$

where:
- $I_{\text{brand}}, I_{\text{cat}}$ = predicted intent scores
- $M_{\text{brand}}, M_{\text{cat}}$ = binary indicators if product matches brand/category
- $\beta_b = 0.3$, $\beta_c = 0.2$ = boost weights

---

### 3.3.5 Bayesian Personalized Ranking (BPR) Recommender

For query-free recommendations based on user behavior history, we employ BPR.

#### Model Architecture

```
User Embeddings: U ∈ ℝ^(|Users| × 64)
Item Embeddings: V ∈ ℝ^(|Items| × 64)

Prediction: score(u, i) = U[u]^\top V[i]
```

#### BPR Loss Function

BPR optimizes for pairwise ranking of items:

$$\mathcal{L}_{\text{BPR}} = -\sum_{(u, i, j) \in D_S} \log \sigma(\hat{x}_{ui} - \hat{x}_{uj}) + \lambda \|\Theta\|^2$$

where:
- $(u, i, j)$ = triplet where user $u$ interacted with item $i$ but not item $j$
- $\hat{x}_{ui} = U[u]^\top V[i]$ = predicted preference score
- $\lambda$ = L2 regularization weight

#### Sampling Strategy

For each positive interaction $(u, i)$, we sample one negative item $j$ uniformly from items the user has not interacted with.

---

### 3.3.6 User Embedding Module

The personalization component constructs user representations from behavioral history.

#### Weighted History Aggregation

Let $H_u = \{(i_1, b_1), (i_2, b_2), ..., (i_n, b_n)\}$ denote user $u$'s interaction history, where $i_k$ is an item and $b_k$ is the behavior type.

The user embedding is computed as:

$$e_u = \frac{\sum_{k=1}^{|H_u|} w_k \cdot e_{i_k}}{\sum_{k=1}^{|H_u|} w_k}$$

where the weight $w_k$ incorporates:

1. **Behavioral Hierarchy** $B_{\text{type}}$:
   - View = 1.0
   - Add to Cart = 3.0
   - Purchase = 10.0

2. **Recency Decay** $D_{\text{time}}(k)$:
   $$D_{\text{time}}(k) = \exp\left(-\frac{t_{\text{now}} - t_k}{\tau}\right)$$
   where $\tau$ = decay constant (e.g., 7 days)

3. **Combined Weight**:
   $$w_k = B_{\text{type}}(b_k) \times D_{\text{time}}(k)$$

---

## 3.4 Training Pipeline and Optimization Strategy

Training a multi-component system requires careful coordination to ensure each module learns effectively. We adopt a **staged training strategy** with three phases.

---

### 3.4.1 Phase 1: Embedding Pre-training

The Sentence Transformer model is used as-is (pre-trained on general text corpora). No fine-tuning is performed in this phase to maintain broad semantic coverage.

**Rationale:** Fine-tuning on e-commerce data may overfit to domain-specific vocabulary and reduce generalization.

---

### 3.4.2 Phase 2: Supervised Ranking Training

The Pairwise LTR model is trained on ESCI labels using the following configuration:

| Parameter | Value |
|-----------|-------|
| Optimizer | Adam |
| Learning Rate | 1e-3 |
| Batch Size | 64 |
| Epochs | 20 |
| Dropout | 0.3 |
| Weight Decay | 1e-5 |

#### Training Pair Construction

For each query, we construct pairs $(d^+, d^-)$ where:
- $d^+$ has label E or S (relevant)
- $d^-$ has label C or I (less relevant)

We use hard negative mining: for each positive, sample negatives from the candidate list retrieved by BM25/Semantic.

#### Loss Function

$$\mathcal{L}_{\text{total}} = \mathcal{L}_{\text{rank}} + \lambda_{\text{intent}} \cdot \mathcal{L}_{\text{intent}}$$

where $\lambda_{\text{intent}} = 0.5$.

---

### 3.4.3 Phase 3: BPR Recommender Training

The BPR model is trained separately on RetailRocket data:

| Parameter | Value |
|-----------|-------|
| Optimizer | Adam |
| Learning Rate | 1e-3 |
| Embedding Dim | 64 |
| Batch Size | 256 |
| Epochs | 50 |
| Negative Samples | 1 per positive |
| L2 Regularization | 1e-4 |

---

### 3.4.4 Regularization Techniques

| Technique | Purpose | Implementation |
|-----------|---------|----------------|
| **Dropout** | Prevent overfitting | Applied after each hidden layer (0.2-0.3) |
| **Weight Decay** | L2 regularization | λ = 1e-5 in Adam optimizer |
| **Early Stopping** | Prevent overtraining | Monitor validation NDCG, patience=5 |
| **Gradient Clipping** | Stabilize training | Max norm = 1.0 |

---

## 3.5 System Implementation Details

### 3.5.1 Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Language | Python | 3.10 |
| Deep Learning | PyTorch | 2.0.0 |
| Embeddings | Sentence Transformers | 2.2.0 |
| Vector Search | FAISS | 1.7.4 |
| Data Processing | pandas, numpy | Latest |
| Evaluation | scikit-learn | 1.2.0 |
| API Framework | FastAPI | 0.100.0 |

### 3.5.2 Hardware Environment

| Resource | Specification |
|----------|---------------|
| GPU | NVIDIA Tesla T4 (16 GB VRAM) |
| CPU | Intel Xeon @ 2.20 GHz |
| RAM | 12 GB |
| Platform | Google Colab Pro |

### 3.5.3 Code Structure

```
src/
├── data/
│   ├── loaders.py          # Dataset loading utilities
│   ├── preprocessing.py    # Text cleaning, feature engineering
│   └── splits.py           # Train/val/test splitting logic
│
├── retrieval/
│   ├── bm25.py             # BM25 sparse retrieval
│   ├── semantic.py         # Dense retrieval with FAISS
│   └── hybrid.py           # RRF fusion implementation
│
├── ranking/
│   ├── pairwise_ltr.py     # Pairwise LTR model definition
│   ├── intent.py           # DeepCAT intent model
│   └── trainer.py          # Training loop and optimization
│
├── personalization/
│   ├── user_embedding.py   # User representation module
│   └── personalized_search.py
│
├── recommendation/
│   └── bpr.py              # BPR recommender implementation
│
├── evaluation/
│   ├── metrics.py          # NDCG, MRR, Hit Rate
│   └── evaluator.py        # Evaluation orchestration
│
└── api/
    ├── search.py           # POST /search endpoint
    └── recommend.py        # POST /recommend endpoint
```

### 3.5.4 Key Hyperparameters

| Parameter | Value | Description |
|-----------|-------|-------------|
| Embedding Dim | 384 | Sentence Transformer output |
| LTR Hidden Dims | [256, 128, 64] | MLP layer sizes |
| BPR Embedding Dim | 64 | User/Item embedding size |
| RRF k | 60 | Fusion constant |
| Top-K Retrieval | 100 | Candidates per query |
| Final Top-N | 10 | Results returned to user |

---

## 3.6 Evaluation Methodology

### 3.6.1 Metrics

Since the system outputs a ranked list of items, we use ranking-oriented metrics:

#### NDCG@K (Normalized Discounted Cumulative Gain)

Measures ranking quality with position-sensitive weighting:

$$\text{NDCG@K} = \frac{\text{DCG@K}}{\text{IDCG@K}}$$

where:

$$\text{DCG@K} = \sum_{i=1}^{K} \frac{2^{rel_i} - 1}{\log_2(i + 1)}$$

#### MRR (Mean Reciprocal Rank)

Measures how early the first relevant item appears:

$$\text{MRR} = \frac{1}{|Q|} \sum_{q \in Q} \frac{1}{\text{rank}_q}$$

#### Hit Rate@K

Measures the proportion of queries with at least one relevant item in top-K:

$$\text{HR@K} = \frac{|\{q : \exists \text{ relevant item in top-K}\}|}{|Q|}$$

---

### 3.6.2 Baselines

| Configuration | Components |
|---------------|------------|
| **BM25 Only** | Lexical retrieval only |
| **Semantic Only** | Dense retrieval only |
| **Hybrid (no LTR)** | RRF fusion, no re-ranking |
| **Hybrid + LTR** | Full pipeline, no personalization |
| **Full System** | All components enabled |

---

### 3.6.3 Ablation Study Design

To understand each component's contribution, we conduct systematic ablations:

| Experiment | Disabled Component | Purpose |
|------------|-------------------|---------|
| A1 | Semantic Retriever | Measure semantic contribution |
| A2 | BM25 Retriever | Measure lexical contribution |
| A3 | LTR Re-ranker | Measure ranking contribution |
| A4 | Intent Boost | Measure intent understanding |
| A5 | Personalization | Measure personalization impact |

---

### 3.6.4 Statistical Significance

All comparisons are validated using:
- **Paired t-test** with p < 0.05 threshold
- **5-fold cross-validation** for robust estimates
- **95% confidence intervals** reported for all metrics

---

## 3.7 Summary

This chapter presented a comprehensive design for an intelligent e-commerce search and recommendation system. The key contributions include:

1. **Hybrid Retrieval Architecture**: Combining BM25 lexical matching with semantic dense retrieval via Reciprocal Rank Fusion, achieving superior recall compared to single-mode approaches.

2. **Pairwise Learning-to-Rank**: A neural re-ranker trained on relative preferences, directly optimizing for ranking quality rather than pointwise relevance.

3. **Multi-task Intent Understanding**: The DeepCAT model captures implicit brand and category intent, enabling targeted relevance boosting.

4. **Behavioral Personalization**: User embeddings constructed from weighted interaction history enable personalized result ordering.

5. **Query-free Recommendations**: BPR-based collaborative filtering provides recommendations when no explicit query is available.

The modular architecture allows for incremental improvements and A/B testing of individual components. The staged training strategy ensures stable optimization across all modules. In the next chapter, we present experimental results demonstrating the effectiveness of each component and the overall system performance compared to traditional approaches.

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
