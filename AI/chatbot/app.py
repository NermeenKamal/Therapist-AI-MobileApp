from fastapi import FastAPI, Request, HTTPException
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM, AutoConfig
import torch
from typing import Optional
import logging
from pydantic import BaseModel

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Therapy Chatbot API",
    description="API for the Therapy T5 Small Fine-Tuned Chatbot",
    version="1.0.0"
)

# Model configuration
MODEL_ID = "Nermeenkamal888/Therapy-T5-Small-Fine-Tuned-Chatbot"
CACHE_DIR = "./model_cache"
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

# Request model
class ChatRequest(BaseModel):
    message: str
    max_length: Optional[int] = 100
    temperature: Optional[float] = 0.7

# Response model
class ChatResponse(BaseModel):
    response: str
    status: str = "success"

# Health response model
class HealthResponse(BaseModel):
    status: str
    model: str
    device: str
    torch_version: str

# Load model and tokenizer
try:
    logger.info("Loading model and tokenizer...")
    
    config = AutoConfig.from_pretrained(MODEL_ID, cache_dir=CACHE_DIR)
    tokenizer = AutoTokenizer.from_pretrained(MODEL_ID, cache_dir=CACHE_DIR)
    model = AutoModelForSeq2SeqLM.from_pretrained(
        MODEL_ID,
        cache_dir=CACHE_DIR,
        config=config
    ).to(DEVICE)
    
    logger.info(f"Model loaded successfully on device: {DEVICE}")
    
except Exception as e:
    logger.error(f"Error loading model: {str(e)}")
    raise RuntimeError(f"Failed to load model: {str(e)}")

@app.get("/", include_in_schema=False)
def root():
    return {"message": "Therapy Chatbot is live 🧠"}

@app.post("/chat/send-message", response_model=ChatResponse)
async def chat(request: Request):
    try:
        data = await request.json()
        chat_request = ChatRequest(**data)
        
        logger.info(f"Received message: {chat_request.message[:50]}...")
        
        # Tokenize input
        inputs = tokenizer.encode(
            chat_request.message,
            return_tensors="pt",
            truncation=True,
            max_length=512
        ).to(DEVICE)
        
        # Generate response
        outputs = model.generate(
            inputs,
            max_new_tokens=chat_request.max_length,
            temperature=chat_request.temperature,
            num_beams=5,
            early_stopping=True,
            do_sample=True
        )
        
        # Decode response
        decoded = tokenizer.decode(
            outputs[0],
            skip_special_tokens=True
        )
        
        logger.info(f"Generated response: {decoded[:50]}...")
        
        return {
            "response": decoded,
            "status": "success"
        }
        
    except Exception as e:
        logger.error(f"Error processing request: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=str(e)
        )

@app.get("/health", response_model=HealthResponse)
def health_check():
    return {
        "status": "healthy",
        "model": MODEL_ID,
        "device": DEVICE,
        "torch_version": torch.__version__
    }

# Error handlers
@app.exception_handler(HTTPException)
async def http_exception_handler(request, exc):
    return JSONResponse(
        status_code=exc.status_code,
        content={"status": "error", "detail": exc.detail}
    )

@app.exception_handler(Exception)
async def generic_exception_handler(request, exc):
    logger.error(f"Unhandled exception: {str(exc)}")
    return JSONResponse(
        status_code=500,
        content={"status": "error", "detail": "Internal server error"}
    )
