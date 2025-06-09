from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSequenceClassification
import torch
import os
from dotenv import load_dotenv
import warnings
warnings.filterwarnings("ignore", category=FutureWarning)

# تحميل المتغيرات البيئية
load_dotenv()

print("==== BERT API IS STARTING ====")

app = FastAPI(title="BERT Model API")

# إعداد CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# تحميل النموذج والتوكينايزر
try:
    print("==== Loading model... ====")
    tokenizer = AutoTokenizer.from_pretrained("distilbert-base-uncased")
    model = AutoModelForSequenceClassification.from_pretrained("distilbert-base-uncased")

    model.eval()
    print("==== Model loaded! ====")
except Exception as e:
    print(f"==== Error loading model: {e} ====")
    raise

class TextInput(BaseModel):
    text: str

@app.get("/")
async def root():
    return {"message": "BERT Model API is running"}

@app.post("/analyze")
async def analyze_text(input_data: TextInput):
    try:
        print("==== /analyze CALLED ====")
        inputs = tokenizer(input_data.text, return_tensors="pt", truncation=True, padding=True)
        with torch.no_grad():
            outputs = model(**inputs)
            predictions = torch.nn.functional.softmax(outputs.logits, dim=-1)

        results = predictions[0].tolist()
        score = max(results)
        label_index = results.index(score)
        label = f"{label_index + 1}-star"

        return {
            "score": score,
            "label": label,
            "feedback": f"The message seems to be {label} sentiment."
        }

    except Exception as e:
        print(f"==== ERROR: {e} ====")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("PORT", 8000))
    uvicorn.run(app, host="0.0.0.0", port=port)
