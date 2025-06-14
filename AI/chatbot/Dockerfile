FROM python:3.10-slim

WORKDIR /app

# Install system dependencies first
RUN apt-get update && \
    apt-get install -y --no-install-recommends g++ cmake && \
    rm -rf /var/lib/apt/lists/*

COPY requirements.txt .

RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

COPY app.py .

# For better performance with torch
ENV OMP_NUM_THREADS=1

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
