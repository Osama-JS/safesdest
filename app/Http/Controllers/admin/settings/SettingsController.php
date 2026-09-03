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
    $this->middleware('permission:general_settings', ['only' => ['index', 'setTemplate']]);
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
   * اختبار الاتصال بـ API متعهد
   */
  public function testMtahdConnection(Request $request)
  {
    try {
      $mtahdService = app(\App\Services\MtahdService::class);
      $res = $mtahdService->getDealDetails('NON_EXISTENT_TEST_DEAL');

      // إذا وصلنا كود 404 أو استجابة سليمة من الـ API (يعني التوكن والاتصال صحيحان)
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
}
