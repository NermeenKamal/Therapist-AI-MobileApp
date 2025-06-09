FROM python:3.9-slim

WORKDIR /app

# تثبيت الأدوات الأساسية
RUN apt-get update && apt-get install -y \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# نسخ الملفات الضرورية فقط
COPY requirements.txt . 
RUN pip install --upgrade pip && pip install --no-cache-dir -r requirements.txt

COPY app.py .

# إنشاء مجلد للكاش
RUN mkdir -p /app/cache
ENV TRANSFORMERS_CACHE=/app/cache

EXPOSE 8000

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
