# AI Models API

هذا المشروع يحتوي على نماذج الذكاء الاصطناعي المستخدمة في تطبيق المعالج الذكي.

## النماذج المتوفرة

### 1. BERT Model
- **الغرض**: تحليل المشاعر في النصوص
- **المنفذ**: 8001
- **النقاط النهائية**:
  - `GET /`: التحقق من حالة الخدمة
  - `POST /analyze`: تحليل النص

### 2. Chatbot
- **الغرض**: المحادثة الذكية
- **المنفذ**: 8002
- **النقاط النهائية**:
  - `GET /`: التحقق من حالة الخدمة
  - `POST /chat`: إرسال رسالة والحصول على رد

## متطلبات التشغيل

- Docker
- Docker Compose
- 4GB RAM على الأقل

## طريقة التشغيل المحلية

1. تشغيل الخدمات:
```bash
docker-compose up --build
```

2. الوصول إلى الخدمات:
- BERT API: http://localhost:8001
- Chatbot API: http://localhost:8002

## النشر على Railway

### المتطلبات المسبقة
1. حساب على [Railway](https://railway.app/)
2. تثبيت [Railway CLI](https://docs.railway.app/develop/cli)
3. حساب على [GitHub](https://github.com)

### خطوات النشر

1. **تسجيل الدخول إلى Railway**:
```bash
railway login
```

2. **تهيئة المشروع**:
```bash
railway init
```

3. **إنشاء مشروعين منفصلين**:
```bash
# لنموذج BERT
cd bert
railway init --name bert-model

# لنموذج Chatbot
cd ../chatbot
railway init --name chatbot-model
```

4. **تعيين المتغيرات البيئية**:
```bash
# لنموذج BERT
railway variables set PORT=8000

# لنموذج Chatbot
railway variables set PORT=8000
```

5. **النشر**:
```bash
# لنموذج BERT
cd bert
railway up

# لنموذج Chatbot
cd ../chatbot
railway up
```

### الوصول إلى الخدمات المنشورة

بعد النشر، ستحصل على روابط للخدمات المنشورة. يمكنك استخدام هذه الروابط في تطبيقك:

```bash
# مثال على استخدام BERT API
curl -X POST "https://bert-model.up.railway.app/analyze" \
     -H "Content-Type: application/json" \
     -d '{"text": "أنا سعيد جداً اليوم"}'

# مثال على استخدام Chatbot API
curl -X POST "https://chatbot-model.up.railway.app/chat" \
     -H "Content-Type: application/json" \
     -d '{"message": "مرحباً", "chat_history": []}'
```

## ملاحظات هامة

- تأكد من وجود مساحة كافية على القرص الصلب
- تأكد من وجود ذاكرة RAM كافية
- في بيئة الإنتاج، قم بتعديل إعدادات CORS لتكون أكثر أماناً
- قم بتحديث روابط API في تطبيقك بعد النشر
- راقب استخدام الموارد في لوحة تحكم Railway 