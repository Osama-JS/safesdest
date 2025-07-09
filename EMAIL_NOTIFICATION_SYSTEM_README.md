# 🚀 نظام الإشعارات عبر الإيميل

نظام متكامل لإرسال الإشعارات عبر البريد الإلكتروني باستخدام Laravel Jobs & Queues مع إمكانية إرفاق الملفات.

## 📋 المميزات

- ✅ **إرسال غير متزامن** باستخدام Laravel Queues
- ✅ **إمكانية إرفاق الملفات** (PDF, DOC, Images, etc.)
- ✅ **نظام إعادة المحاولة التلقائي** مع backoff strategy
- ✅ **تسجيل شامل للعمليات** مع قاعدة بيانات
- ✅ **قوالب متعددة** للإيميلات
- ✅ **نظام أولويات** للإرسال (High, Normal, Low)
- ✅ **معالجة الإشعارات المتأخرة** تلقائياً
- ✅ **تنظيف السجلات القديمة** تلقائياً
- ✅ **نظام مراقبة وإحصائيات** شامل
- ✅ **Rate Limiting** لمنع الإرسال المفرط
- ✅ **Facade & Helper Classes** للاستخدام السهل

## 🛠️ التثبيت والإعداد

### 1. تشغيل Migration

```bash
php artisan migrate
```

### 2. إعداد متغيرات البيئة (.env)

```env
# إعدادات البريد الإلكتروني
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# إعدادات Queue
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default

# إعدادات الإشعارات (اختيارية)
NOTIFICATION_RATE_LIMITING=true
NOTIFICATION_MAX_PER_MINUTE=60
NOTIFICATION_MAX_PER_HOUR=1000
NOTIFICATION_LOGGING=true
```

### 3. تسجيل Service Provider (إذا لم يتم تلقائياً)

في `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\EmailNotificationServiceProvider::class,
],

'aliases' => [
    // ...
    'EmailNotification' => App\Facades\EmailNotification::class,
],
```

### 4. تشغيل Queue Worker

```bash
# للتطوير
php artisan queue:work

# للإنتاج مع إعدادات محسنة
php artisan queue:work --queue=high,default,low --timeout=60 --sleep=3 --tries=3
```

## 💡 طرق الاستخدام

### 1. الاستخدام الأساسي

```php
use App\Services\EmailNotificationService;

$emailService = new EmailNotificationService();

$emailService->send([
    'to' => 'user@example.com',
    'subject' => 'إشعار جديد',
    'content' => 'محتوى الإشعار هنا',
    'user_name' => 'أحمد محمد'
]);
```

### 2. استخدام Facade

```php
use App\Facades\EmailNotification;

EmailNotification::send([
    'to' => 'user@example.com',
    'subject' => 'إشعار جديد',
    'content' => 'محتوى الإشعار',
    'priority' => 'high'
]);
```

### 3. استخدام Helper Class

```php
use App\Helpers\NotificationHelper;

// إرسال إشعار تعيين مهمة
NotificationHelper::sendTaskAssigned($driver, $task);

// إرسال إشعار استلام دفعة
NotificationHelper::sendPaymentReceived($user, 500, 'TXN123');

// إرسال ترحيب
NotificationHelper::sendWelcome($user, 'driver');
```

### 4. إرسال مع مرفقات

```php
$attachments = [
    [
        'path' => 'invoices/invoice_123.pdf',
        'name' => 'فاتورة_123.pdf',
        'mime' => 'application/pdf'
    ],
    [
        'data' => $pdfContent, // Raw data
        'name' => 'report.pdf',
        'mime' => 'application/pdf'
    ]
];

$emailService->send([
    'to' => 'customer@example.com',
    'subject' => 'فاتورتك جاهزة',
    'content' => 'تجد مرفق فاتورتك'
], $attachments);
```

### 5. الإرسال المجمع

```php
$recipients = ['user1@example.com', 'user2@example.com'];

$emailService->sendBulk($recipients, [
    'subject' => 'إشعار صيانة النظام',
    'content' => 'سيتم إجراء صيانة للنظام غداً',
    'priority' => 'high'
]);
```

### 6. الإرسال المؤجل

```php
// إرسال بعد 30 دقيقة
$emailService->send([
    'to' => 'user@example.com',
    'subject' => 'تذكير',
    'content' => 'لا تنس مهمتك'
], [], '30 minutes');

// إرسال بأولوية منخفضة مع تأخير
$emailService->sendLowPriority([
    'to' => 'user@example.com',
    'subject' => 'تقرير أسبوعي',
    'content' => 'تقرير الأنشطة'
], [], '1 hour');
```

## 🎨 القوالب المتاحة

- `emails.notification` - قالب عام
- `emails.task-assigned` - تعيين مهمة
- `emails.payment-received` - استلام دفعة
- `emails.welcome` - ترحيب
- `emails.system-maintenance` - صيانة

### إنشاء قالب جديد

```blade
{{-- resources/views/emails/custom-template.blade.php --}}
@extends('emails.notification')

@section('content')
<div class="greeting">
    مرحباً {{ $user_name }}،
</div>

<div class="content">
    {!! $content !!}
</div>

@if(isset($action_url))
    <div class="action-section">
        <a href="{{ $action_url }}" class="action-button">
            {{ $action_text ?? 'اضغط هنا' }}
        </a>
    </div>
@endif
@endsection
```

## 🔧 الأوامر المتاحة

### معالجة الإشعارات المتأخرة

```bash
php artisan notifications:process-pending
php artisan notifications:process-pending --limit=100 --max-attempts=5
```

### تنظيف السجلات القديمة

```bash
php artisan notifications:cleanup
php artisan notifications:cleanup --success-days=7 --failed-days=30 --dry-run
```

## 📊 المراقبة والإحصائيات

```php
use App\Models\EmailNotificationLog;

// إحصائيات اليوم
$todayStats = [
    'sent' => EmailNotificationLog::sent()->today()->count(),
    'failed' => EmailNotificationLog::failed()->today()->count(),
    'pending' => EmailNotificationLog::pending()->today()->count()
];

// معدل النجاح
$successRate = EmailNotificationLog::thisMonth()
    ->sent()->count() / EmailNotificationLog::thisMonth()->count() * 100;
```

## ⚠️ أفضل الممارسات

1. **استخدم الأولويات بحكمة**
   - `high`: للإشعارات العاجلة فقط
   - `normal`: للإشعارات العادية
   - `low`: للتقارير والنشرات

2. **راقب Queue Workers**
   ```bash
   php artisan queue:failed
   php artisan queue:retry all
   ```

3. **استخدم Redis في الإنتاج**
   ```env
   QUEUE_CONNECTION=redis
   ```

4. **قم بتنظيف السجلات دورياً**
   ```bash
   # في crontab
   0 2 * * * php /path/to/artisan notifications:cleanup
   ```

## 🔒 الأمان

- ✅ تحقق من صحة عناوين البريد
- ✅ استخدم HTTPS للروابط
- ✅ لا تضع معلومات حساسة في المحتوى
- ✅ راقب محاولات الإرسال المشبوهة
- ✅ استخدم Rate Limiting

## 🐛 استكشاف الأخطاء

### الإشعارات لا تُرسل

```bash
# تحقق من Queue
php artisan queue:failed

# إعادة تشغيل Workers
php artisan queue:restart

# تحقق من إعدادات البريد
php artisan tinker
Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

### الإشعارات بطيئة

```bash
# زيادة عدد Workers
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# استخدام Redis
QUEUE_CONNECTION=redis
```

## 📞 الدعم

للدعم الفني أو الاستفسارات، يرجى التواصل مع فريق التطوير.

---

© 2024 نظام الإشعارات عبر الإيميل - جميع الحقوق محفوظة
