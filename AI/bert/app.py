from fastapi import FastAPI
from transformers import AutoTokenizer, AutoModelForSequenceClassification
import torch

app = FastAPI()

MODEL_NAME = "prajjwal1/bert-tiny"  

tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)
model = AutoModelForSequenceClassification.from_pretrained(MODEL_NAME)

@app.get("/")
def read_root():
    return {"message": "Model is running!"}

@app.post("/predict")
def predict(text: str):
    inputs = tokenizer(text, return_tensors="pt", truncation=True, padding=True)
    outputs = model(**inputs)
    probs = torch.nn.functional.softmax(outputs.logits, dim=-1)
    confidence, prediction = torch.max(probs, dim=1)
    return {
        "prediction": prediction.item(),
        "confidence": round(confidence.item(), 4)
    }
