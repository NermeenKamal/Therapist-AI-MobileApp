FROM python:3.9-slim

WORKDIR /app

# تثبيت الأدوات المطلوبة فقط ثم حذفها فوراً لتقليل الحجم
RUN apt-get update && apt-get install -y \
    build-essential \
    && pip install --upgrade pip \
    && apt-get remove -y build-essential \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app.py .

# تقليل حجم الـ cache للنماذج
ENV TRANSFORMERS_CACHE=/tmp/cache
RUN mkdir -p /tmp/cache

EXPOSE 8000

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
