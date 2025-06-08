from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSequenceClassification
import torch
import os
from dotenv import load_dotenv

# تحميل المتغيرات البيئية
load_dotenv()

print("==== Chatbot API IS STARTING ====")

app = FastAPI(title="BERT Model API")

# إعداد CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # في الإنتاج، حدد النطاقات المسموح بها
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

print("==== Loading model... ====")
# تحميل النموذج
print("==== Model loaded! ====")

class TextInput(BaseModel):
    text: str

@app.get("/")
async def root():
    return {"message": "BERT Model API is running"}

@app.post("/analyze")
async def analyze_text(input_data: TextInput):
    try:
        # تحضير النص
        print("==== /analyze CALLED ====")
        print(f"Received text: {input_data.text}")
        inputs = tokenizer(input_data.text, return_tensors="pt", truncation=True, padding=True)
        
        # التنبؤ
        with torch.no_grad():
            outputs = model(**inputs)
            predictions = torch.nn.functional.softmax(outputs.logits, dim=-1)
            
        # تحويل النتائج إلى قائمة
        results = predictions[0].tolist()
        
        return {
            "text": input_data.text,
            "predictions": results,
            "sentiment": "positive" if results[1] > results[0] else "negative"
        }
    except Exception as e:
        print(f"==== ERROR: {e} ====")
        raise

if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("PORT", 8000))
    print("==== Starting Uvicorn server... ====")
    uvicorn.run(app, host="0.0.0.0", port=port) 
