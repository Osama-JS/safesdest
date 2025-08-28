# Firebase Service Account Key

## إعداد مفتاح الخدمة

1. اذهب إلى Firebase Console
2. اختر مشروعك
3. اذهب إلى Project Settings > Service Accounts
4. انقر على "Generate new private key"
5. احفظ الملف باسم `service-account-key.json` في هذا المجلد

## ملاحظات مهمة

- لا تشارك هذا الملف مع أي شخص
- لا تضعه في نظام التحكم بالإصدارات (Git)
- تأكد من أن المسار في .env صحيح:
  ```
  FIREBASE_CREDENTIALS=storage/firebase/service-account-key.json
  ```

## هيكل الملف المطلوب

```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "...",
  "private_key": "...",
  "client_email": "...",
  "client_id": "...",
  "auth_uri": "...",
  "token_uri": "...",
  "auth_provider_x509_cert_url": "...",
  "client_x509_cert_url": "..."
}
```
