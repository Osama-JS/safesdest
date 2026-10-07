<?php

namespace App\Http\Controllers\admin\settings;

use App\Http\Controllers\Controller;
use App\Models\Form_Template;
use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:general_settings', ['only' => ['index', 'setTemplate', 'updateMailSettings', 'testMailConnection', 'updateSaeiSettings', 'testSaeiConnection']]);
  }

  public function index()
  {
    $templates = Form_Template::all();
    $settings = Settings::get()->keyBy('key')->map(function ($item) {
      return [
        'value' => $item->value,
        'description' => $item->description,
        'name' => $item->name,
        'type' => $item->type,
        'options' => $item->options,
      ];
    })->toArray();

    return view('admin.settings.index', compact('templates', 'settings'));
  }

  public function setTemplate(Request $req)
  {
    $req->validate([
      'key' => 'required|string',
      'value' => 'nullable|string'
    ]);

    $setting = Settings::firstOrNew(['key' => $req->key]);
    $setting->value = $req->value;
    $setting->save();

    return response()->json(['success' => true, 'message' => 'Setting updated successfully']);
  }

  /**
   * إنشاء وتحديث حساب المنصة كبائع معتمد في متعهد
   */
  public function createMtahdPlatformAccount(Request $request)
  {
    try {
      $mtahdService = app(\App\Services\MtahdService::class);
      
      $platformName = $request->input('name', 'منصة سيف ديست للخدمات اللوجستية (SafeDests)');
      $platformPhone = $request->input('phone', '+966500000000');
      $platformEmail = $request->input('email', 'finance@safedests.com');

      $res = $mtahdService->createCustomer([
        'name'         => $platformName,
        'phone_number' => $platformPhone,
        'email'        => $platformEmail,
        'type'         => 'company',
      ]);

      if ($res['status'] && isset($res['data']['customer_number'])) {
        $customerNumber = $res['data']['customer_number'];
        
        Settings::updateOrCreate(
          ['key' => 'mtahd_platform_customer_number'],
          [
            'value' => $customerNumber,
            'name'  => 'رقم حساب المنصة في متعهد',
            'description' => 'المعرف الرقمي لحساب المنصة كبائع معتمد في منصة أمن/متعهد'
          ]
        );

        return response()->json([
          'success' => true,
          'customer_number' => $customerNumber,
          'message' => 'تم إنشاء وتوثيق حساب المنصة في متعهد بنجاح: ' . $customerNumber
        ]);
      }

      return response()->json([
        'success' => false,
        'message' => $res['error'] ?? 'فشل في إنشاء الحساب في منصة متعهد',
        'details' => $res['details'] ?? null
      ], 400);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * تحديث وحفظ إعدادات خادم البريد (SMTP)
   */
  public function updateMailSettings(Request $request)
  {
    $validated = $request->validate([
      'mail_mailer' => 'required|string|in:smtp,sendmail,log',
      'mail_host' => 'nullable|string',
      'mail_port' => 'nullable|numeric',
      'mail_username' => 'nullable|string',
      'mail_password' => 'nullable|string',
      'mail_encryption' => 'nullable|string',
      'mail_from_address' => 'required|email',
      'mail_from_name' => 'required|string|max:100',
    ]);

    foreach ($validated as $key => $value) {
      // Do not overwrite existing password if submitted empty
      if ($key === 'mail_password' && empty($value)) {
        continue;
      }

      Settings::updateOrCreate(
        ['key' => $key],
        [
          'value' => $value,
          'category' => 'mail',
          'name' => 'إعدادات البريد - ' . $key,
        ]
      );
    }

    // Re-apply runtime config immediately
    \App\Services\MailConfigService::apply();

    return response()->json([
      'success' => true,
      'message' => 'تم حفظ وتحديث إعدادات خادم البريد الإلكتروني بنجاح!'
    ]);
  }

  /**
   * اختبار اتصال خادم البريد وإرسال بريد تجريبي
   */
  public function testMailConnection(Request $request)
  {
    $request->validate([
      'test_email' => 'required|email'
    ]);

    try {
      // If custom parameters were submitted, apply them temporarily for the test
      if ($request->filled('mail_host')) {
        \App\Services\MailConfigService::applyCustom([
          'mail_mailer' => $request->input('mail_mailer', 'smtp'),
          'mail_host' => $request->input('mail_host'),
          'mail_port' => $request->input('mail_port'),
          'mail_username' => $request->input('mail_username'),
          'mail_password' => $request->input('mail_password'),
          'mail_encryption' => $request->input('mail_encryption'),
          'mail_from_address' => $request->input('mail_from_address'),
          'mail_from_name' => $request->input('mail_from_name'),
        ]);
      } else {
        \App\Services\MailConfigService::apply();
      }

      $recipient = $request->input('test_email');
      $now = now()->toDateTimeString();
      $host = config('mail.mailers.smtp.host');
      $port = config('mail.mailers.smtp.port');
      $from = config('mail.from.address');
      $name = config('mail.from.name');

      \Illuminate\Support\Facades\Mail::raw(
        "مرحباً بك،\n\n" .
        "هذه رسالة اختبارية لتأكيد صحة إعدادات خادم البريد الإلكتروني (SMTP) في منصة سيف ديست (SafeDest).\n\n" .
        "بيانات الاتصال المستخدمة في هذا الاختبار:\n" .
        "- خادم البريد (Host): {$host}:{$port}\n" .
        "- البريد المرسل منه (From): {$from} ({$name})\n" .
        "- توقيت الإرسال: {$now}\n\n" .
        "وصول هذا البريد إليك يؤكد أن بيانات الاتصال دقيقة وتعمل بنجاح تام وبدون أي مشاكل.",
        function ($message) use ($recipient) {
          $message->to($recipient)
                  ->subject('اختبار اتصال خادم البريد الإلكتروني (SMTP) - SafeDest');
        }
      );

      // Restore saved database settings
      \App\Services\MailConfigService::apply();

      return response()->json([
        'success' => true,
        'message' => "تم إرسال البريد التجريبي بنجاح إلى: {$recipient}، يرجى التحقق من صندوق الوارد."
      ]);
    } catch (\Throwable $e) {
      // Restore saved database settings in case of failure
      \App\Services\MailConfigService::apply();

      return response()->json([
        'success' => false,
        'message' => 'فشل إرسال البريد: ' . $e->getMessage()
      ], 400);
    }
  }

  /**
   * اختبار الاتصال بـ API متعهد
   */
  public function testMtahdConnection(Request $request)
  {
    try {
      $mtahdService = app(\App\Services\MtahdService::class);
      $res = $mtahdService->getDealDetails('NON_EXISTENT_TEST_DEAL');

      return response()->json([
        'success' => true,
        'message' => 'تم الاتصال بـ API منصة متعهد بنجاح والتوكن يعمل بصورة ممتازة!'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'فشل الاتصال بـ API متعهد: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * تحديث وحفظ إعدادات الربط مع ساعي وواتساب (Saei & WhatsApp Cloud API)
   */
  public function updateSaeiSettings(Request $request)
  {
    $validated = $request->validate([
      'whatsapp_provider'      => 'nullable|string|in:saei,cloud,green',
      'saei_otp_enabled'       => 'nullable|string|in:0,1',
      'saei_simulation'        => 'nullable|string|in:0,1',
      'saei_api_key'           => 'nullable|string',
      'saei_base_url'          => 'nullable|url',
      'saei_from_phone_id'     => 'nullable|string',
      'saei_template_id'       => 'nullable|numeric',
      'saei_callback_secret'   => 'nullable|string',
      'whatsapp_cloud_token'   => 'nullable|string',
      'whatsapp_cloud_waba_id' => 'nullable|string',
      'whatsapp_cloud_phone_id'=> 'nullable|string',
      'whatsapp_verify_token'  => 'nullable|string',
      'whatsapp_cloud_url'     => 'nullable|url',
    ]);

    $descriptions = [
      'whatsapp_provider'      => 'مزود خدمة الواتساب النشط للمنصة (saei / cloud / green)',
      'saei_otp_enabled'       => 'تفعيل خدمة ساعي لإرسال OTP عبر واتساب',
      'saei_simulation'        => 'وضع المحاكاة لتجربة إرسال OTP بدون خصم رصيد',
      'saei_api_key'           => 'مفتاح الـ API الخاص بمنصة ساعي (Saei Secret Key)',
      'saei_base_url'          => 'الرابط الأساسي لـ API ساعي (Base URL)',
      'saei_from_phone_id'     => 'معرّف رقم الهاتف المُرسِل في ساعي وميتا (Phone Number ID)',
      'saei_template_id'       => 'رقم معرّف قالب OTP في ساعي (Template ID)',
      'saei_callback_secret'   => 'المفتاح السري لتوقيع Callback لساعي',
      'whatsapp_cloud_token'   => 'رمز الوصول الدائم لحساب واتساب كلاود في ميتا (Cloud Token)',
      'whatsapp_cloud_waba_id' => 'معرّف حساب واتساب للأعمال (WABA ID)',
      'whatsapp_cloud_phone_id'=> 'معرّف رقم واتساب السحابي (Cloud Phone ID)',
      'whatsapp_verify_token'  => 'رمز التحقق الخاص بالويب هوك (Webhook Verify Token)',
      'whatsapp_cloud_url'     => 'رابط Graph API لواتساب كلاود',
    ];

    foreach ($validated as $key => $value) {
      Settings::updateOrCreate(
        ['key' => $key],
        [
          'value'       => $value ?? '',
          'category'    => 'saei',
          'name'        => $descriptions[$key] ?? $key,
          'description' => $descriptions[$key] ?? '',
        ]
      );
    }

    return response()->json([
      'success' => true,
      'message' => 'تم حفظ وتحديث إعدادات ساعي وواتساب بنجاح، وأصبحت سارية المفعول فوراً!'
    ]);
  }

  /**
   * اختبار الاتصال بمنصة ساعي للتحقق من صحة مفتاح الـ API والربط
   */
  public function testSaeiConnection(Request $request)
  {
    try {
      $apiKey = $request->input('saei_api_key') ?: Settings::where('key', 'saei_api_key')->value('value') ?: env('SAEI_API_KEY', '');
      $baseUrl = $request->input('saei_base_url') ?: Settings::where('key', 'saei_base_url')->value('value') ?: env('SAEI_BASE_URL', 'https://api.saei.automize.sa/v1');
      $phoneId = $request->input('saei_from_phone_id') ?: Settings::where('key', 'saei_from_phone_id')->value('value') ?: env('SAEI_FROM_PHONE_ID', '');

      if (empty($apiKey)) {
        return response()->json([
          'success' => false,
          'message' => 'يرجى إدخال مفتاح الـ API الخاص بمنصة ساعي أولاً.'
        ], 422);
      }

      $saeiService = new \App\Services\SaeiWhatsAppService();
      $saeiService->setCredentials($apiKey, $baseUrl, $phoneId);

      $res = $saeiService->getAccountInfo();

      if ($res['success']) {
        $msg = $res['message'] ?? 'تم الاتصال بمنصة ساعي بنجاح والاعتمادات صحيحة!';
        if (!empty($res['numbers'])) {
          $count = count($res['numbers']);
          $msg .= " (عدد الأرقام المرتبطة بحساب ساعي: {$count})";
        }
        return response()->json([
          'success' => true,
          'message' => $msg,
          'data' => $res
        ]);
      }

      return response()->json([
        'success' => false,
        'message' => $res['message'] ?? 'فشل الاتصال بمنصة ساعي. يرجى التأكد من صحة مفتاح الـ API والرابط.'
      ], 400);

    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ أثناء فحص الاتصال بساعي: ' . $e->getMessage()
      ], 500);
    }
  }
}

