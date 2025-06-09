import os
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSequenceClassification
import torch

# استخدام كاش خفيف
os.environ["TRANSFORMERS_CACHE"] = "./cache"

app = FastAPI()

# تحميل نموذج BERT خفيف الوزن
print("🚀 Loading BERT...")
tokenizer = AutoTokenizer.from_pretrained("distilgpt2")
model = AutoModelForCausalLM.from_pretrained("distilgpt2")
model.eval()

class TextInput(BaseModel):
    text: str

@app.get("/")
def read_root():
    return {"message": "BERT Analyzer ready"}

@app.post("/analyze")
def analyze(input: TextInput):
    try:
        inputs = tokenizer(input.text, return_tensors="pt", truncation=True, padding=True)
        with torch.no_grad():
            logits = model(**inputs).logits
        predicted_class = torch.argmax(logits, dim=1).item()
        return {"label": predicted_class}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
