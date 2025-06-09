from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSequenceClassification
import torch
import os
from dotenv import load_dotenv

# تحميل المتغيرات البيئية
load_dotenv()

print("==== BERT API IS STARTING ====")

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
        print("==== /analyze CALLED ====")
        print(f"Received text: {input_data.text}")
        inputs = tokenizer(input_data.text, return_tensors="pt", truncation=True, padding=True)

        with torch.no_grad():
            outputs = model(**inputs)
            predictions = torch.nn.functional.softmax(outputs.logits, dim=-1)

        results = predictions[0].tolist()
        score = max(results)
        label = "positive" if results[1] > results[0] else "negative"

        # feedback بسيط تقديري – ممكن تطوريه لاحقًا
        feedback = f"The message expresses a {label} feeling."

        return {
            "score": score,
            "label": label,
            "feedback": feedback
        }

    except Exception as e:
        print(f"==== ERROR: {e} ====")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("PORT", 8000))
    print("==== Starting Uvicorn server... ====")
    uvicorn.run(app, host="0.0.0.0", port=port) 
