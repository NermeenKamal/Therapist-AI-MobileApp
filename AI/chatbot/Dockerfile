FROM python:3.9-slim

# إعداد مجلد العمل
WORKDIR /app

# نسخ الملفات المطلوبة فقط لتقليل الـ context
COPY requirements.txt .

# تثبيت المتطلبات فقط
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# نسخ الكود الأساسي
COPY . .

# إعداد كاش النموذج المؤقت
ENV TRANSFORMERS_CACHE=/tmp/cache
RUN mkdir -p /tmp/cache

# فتح المنفذ المستخدم داخل Railway
EXPOSE 8000

# أمر التشغيل النهائي
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
