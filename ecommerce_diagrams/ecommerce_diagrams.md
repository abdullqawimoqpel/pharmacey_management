# E-Commerce Search System - Diagrams

## Generated Diagrams for Technical Report

Below are the professional diagrams created for your e-commerce search system documentation:

---

### 1. System Architecture
![Overall System Architecture](C:/Users/computer/.gemini/antigravity/brain/ecafd3a2-9623-4d9a-a00a-791bf24d5394/system_architecture_1765571406060.png)

Shows the complete system from User/Client through API Gateway, Query Processing, Retrieval Layer, Model Layer, to Final Ranking and Output.

---

### 2. Model Layer Architecture
![Model Layer & Neural Networks](C:/Users/computer/.gemini/antigravity/brain/ecafd3a2-9623-4d9a-a00a-791bf24d5394/model_layer_diagram_1765571436280.png)

Details the Pairwise Ranker and DeepCAT Intent Model architectures with input/output shapes.

---

### 3. Data Pipeline
![Data Preprocessing Pipeline](C:/Users/computer/.gemini/antigravity/brain/ecafd3a2-9623-4d9a-a00a-791bf24d5394/data_pipeline_diagram_1765571460831.png)

Shows data flow from 4 sources (ESCI, SIGIR, Home Depot, RetailRocket) through preprocessing to indices.

---

### 4. Training Pipeline
![ML Training Pipeline](C:/Users/computer/.gemini/antigravity/brain/ecafd3a2-9623-4d9a-a00a-791bf24d5394/training_pipeline_1765571524353.png)

Illustrates the training process for Ranker and Intent models with loss functions and monitoring.

---

### 5. Evaluation Metrics
![Model Comparison Chart](C:/Users/computer/.gemini/antigravity/brain/ecafd3a2-9623-4d9a-a00a-791bf24d5394/evaluation_metrics_1765571552158.png)

Comparison of NDCG@10 scores across different model configurations.

---

### 6. Deployment Architecture
![Cloud Deployment Architecture](C:/Users/computer/.gemini/antigravity/brain/ecafd3a2-9623-4d9a-a00a-791bf24d5394/deployment_architecture_1765571584317.png)

Shows the FastAPI service, storage layer, monitoring, and retraining pipeline.

---

## Usage in Report

You can use these diagrams in the following chapters:

| Diagram | Suggested Chapter |
|---------|------------------|
| System Architecture | Chapter: System Design |
| Model Layer | Chapter: Model Design |
| Data Pipeline | Chapter: Data Description |
| Training Pipeline | Chapter: Training & Optimization |
| Evaluation Metrics | Chapter: Experiments & Results |
| Deployment Architecture | Chapter: Deployment Plan |
| Search Flow | Chapter: System Overview |

---

### 7. End-to-End Search Flow
![Search Query Flow](C:/Users/computer/.gemini/antigravity/brain/ecafd3a2-9623-4d9a-a00a-791bf24d5394/search_flow_diagram_1765571649618.png)

Shows the complete journey of a user query from input through processing, retrieval, ranking to final output.

