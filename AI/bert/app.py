from fastapi import FastAPI, Request
import requests
import os

app = FastAPI()

HF_API_URL = "https://api-inference.huggingface.co/models/prajjwal1/bert-tiny"
HF_TOKEN = os.getenv("HF_TOKEN")

# تجهيز الهيدر بالتوكن
headers = {"Authorization": f"Bearer {HF_TOKEN}"}

@app.get("/")
def root():
    return {"message": "Model via HuggingFace API is ready"}

@app.post("/predict")
async def predict(request: Request):
    data = await request.json()
    text = data.get("text", "")
    if not text:
        return {"error": "Missing text"}
    
    response = requests.post(HF_API_URL, headers=headers, json={"inputs": text})
    try:
        result = response.json()
    except:
        return {"error": "Failed to parse Hugging Face response"}
    
    return result
