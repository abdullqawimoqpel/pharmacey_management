# Chapter 3: System Design & Implementation
## Academic Diagrams for Thesis

These diagrams are designed in academic style suitable for thesis/dissertation submission.

---

### Figure 3.1: Overall System Architecture
![Figure 3.1](fig1_system_architecture_1765572048338.png)

Shows the complete system architecture including User Interface, API Gateway, Query Processing, Retrieval Layer (BM25 + Semantic), Ranking Layer, and Output.

---

### Figure 3.2: Model Layer Architecture
![Figure 3.2](fig2_model_architecture_1765572077531.png)

Details the Pairwise Ranker neural network and DeepCAT Intent Model architecture with embedding dimensions and output heads.

---

### Figure 3.3: Data Preprocessing Pipeline
![Figure 3.3](fig3_data_pipeline_1765572101707.png)

Illustrates data flow from 4 sources (ESCI, SIGIR, Home Depot, RetailRocket) through preprocessing to output indices.

---

### Figure 3.4: Training Pipeline
![Figure 3.4](fig4_training_pipeline_1765572187592.png)

Shows the training process for Ranker and Intent models including loss functions, optimizers, and evaluation metrics.

---

### Figure 3.5: Model Comparison Results
![Figure 3.5](fig5_evaluation_results_1765572215651.png)

Bar chart comparing NDCG@10 scores across 5 model configurations from BM25 baseline to full system.

---

### Figure 3.6: End-to-End Query Processing Flow
![Figure 3.6](fig6_search_flow_1765572240499.png)

Horizontal flowchart showing query journey from user input through preprocessing, retrieval, ranking to final results.

---

### Figure 3.7: Deployment Architecture
![Figure 3.7](fig7_deployment_1765572264628.png)

Three-tier deployment architecture showing Client Layer, Service Layer (FastAPI), and Data Layer.

---

## Usage Notes

- All figures use white background with black/gray elements
- Suitable for IEEE, ACM, or standard thesis format
- Recommended to save as PDF for best print quality
