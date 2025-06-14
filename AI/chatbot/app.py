from fastapi import FastAPI
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM

app = FastAPI()

model_id = "Nermeenkamal888/Therapy-T5-Small-Fine-Tuned-Chatbot"
tokenizer = AutoTokenizer.from_pretrained(model_id)
model = AutoModelForSeq2SeqLM.from_pretrained(model_id)

class Message(BaseModel):
    text: str

@app.post("/predict")
async def predict(message: Message):
    inputs = tokenizer.encode(message.text, return_tensors="pt")
    outputs = model.generate(inputs, max_new_tokens=100)
    response = tokenizer.decode(outputs[0], skip_special_tokens=True)
    return {"response": response}
