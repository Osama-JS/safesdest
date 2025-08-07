# دليل التبديل بين Google reCAPTCHA والـ Captcha المخصص

## الوضع الحالي
حالياً يتم استخدام Google reCAPTCHA في كل من:
- صفحة التسجيل (register.blade.php)
- صفحة تسجيل الدخول (custom-login.blade.php)
- التحقق في FortifyServiceProvider.php

## كيفية التبديل إلى الـ Captcha المخصص

### 1. في ملف FortifyServiceProvider.php

**الخطوات:**
1. اذهب إلى السطر 51-53
2. قم بتعليق validation الـ reCAPTCHA:
```php
// $validator = Validator::make($request->all(), [
//   'g-recaptcha-response' => 'required|recaptcha',
// ]);
```

3. قم بإلغاء تعليق validation الـ Captcha المخصص (السطر 60-62):
```php
$validator = Validator::make($request->all(), [
  'captcha' => 'required|captcha',
]);
```

### 2. في ملف custom-login.blade.php

**الخطوات:**
1. اذهب إلى السطر 393-409 وقم بتعليق قسم reCAPTCHA:
```html
{{-- <!-- reCAPTCHA Section -->
<div class="mb-4">
    @error('recaptcha')
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="ti ti-alert-circle me-2"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror
    <div class="captcha-section">
        <label class="form-label mb-3">
            <i class="ti ti-shield-check me-2"></i>Security Verification
        </label>
        <div class="captcha-container">
            {!! htmlFormSnippet() !!}
        </div>
    </div>
</div> --}}
```

2. اذهب إلى السطر 411-439 وقم بإلغاء تعليق قسم الـ Captcha المخصص:
```html
<!-- Custom Captcha Section -->
<div class="mb-4">
    @error('captcha')
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="ti ti-alert-circle me-2"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror
    <div class="captcha-section">
        <label class="form-label mb-3">
            <i class="ti ti-shield-check me-2"></i>Enter the code in the image
        </label>
        <div class="captcha-container d-flex align-items-center gap-3 mb-3">
            <img src="{{ captcha_src() }}" alt="captcha" id="captcha-image" 
                 style="height: 60px; border-radius: 8px; border: 2px solid #e9ecef;">
            <button type="button" class="btn btn-outline-secondary btn-refresh" 
                    onclick="refreshCaptcha()">
                <i class="ti ti-refresh"></i>
            </button>
        </div>
        <input type="text" class="form-control @error('captcha') is-invalid @enderror" 
               name="captcha" placeholder="Enter captcha code" required>
        @error('captcha')
            <div class="invalid-feedback">
                <i class="ti ti-alert-circle me-1"></i>{{ $message }}
            </div>
        @enderror
    </div>
</div>
```

### 3. في ملف register.blade.php

**للتبديل في صفحة التسجيل:**
1. قم بتعليق قسم reCAPTCHA (السطر 452-462)
2. قم بإلغاء تعليق قسم الـ Captcha المخصص (السطر 463-481)

## ملاحظات مهمة

1. **تأكد من وجود مكتبة الـ Captcha**: تأكد من أن مكتبة الـ captcha مثبتة ومُعدة بشكل صحيح
2. **JavaScript للتحديث**: تم إضافة function `refreshCaptcha()` لتحديث صورة الـ captcha
3. **التصميم**: تم إضافة CSS مخصص للـ captcha ليتناسب مع تصميم الموقع
4. **رسائل الخطأ**: تم إعداد رسائل خطأ مناسبة لكل نوع من أنواع الـ captcha

## العودة إلى reCAPTCHA

لإعادة التبديل إلى reCAPTCHA، قم بعكس الخطوات المذكورة أعلاه.
