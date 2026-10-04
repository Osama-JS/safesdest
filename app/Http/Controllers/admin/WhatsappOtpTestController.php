<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SaeiOtpService;
use App\Models\Settings;

class WhatsappOtpTestController extends Controller
{
    protected SaeiOtpService $saei;

    public function __construct(SaeiOtpService $saei)
    {
        $this->middleware('permission:view_whatsapp_logs');
        $this->saei = $saei;
    }

    /**
     * عرض صفحة اختبار OTP
     */
    public function index()
    {
        $saeiEnabled = filter_var(Settings::getValue('saei_otp_enabled', env('SAEI_OTP_ENABLED', true)), FILTER_VALIDATE_BOOLEAN);
        $apiKey = Settings::getValue('saei_api_key', env('SAEI_API_KEY', ''));

        $config = [
            'provider'        => $saeiEnabled ? 'ساعي (Saei)' : 'WhatsApp Cloud API',
            'saei_enabled'    => $saeiEnabled,
            'simulation'      => filter_var(Settings::getValue('saei_simulation', env('SAEI_SIMULATION', false)), FILTER_VALIDATE_BOOLEAN),
            'api_key_set'     => !empty($apiKey) && $apiKey !== 'sk_live_WgWLxp_YOUR_FULL_KEY_HERE',
            'from_phone'      => Settings::getValue('saei_from_phone_id', env('SAEI_FROM_PHONE_ID', '—')),
            'template_id'     => Settings::getValue('saei_template_id', env('SAEI_TEMPLATE_ID', '—')),
        ];

        return view('admin.whatsapp-otp-test.index', compact('config'));
    }

    /**
     * إرسال OTP تجريبي عبر ساعي
     * POST /admin/whatsapp-otp-test/send
     */
    public function send(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:9'],
        ]);

        $phone = $request->phone;
        // تأكد أن الرقم يبدأ بـ +
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . ltrim($phone, '0');
        }

        Log::info('[AdminOTPTest] Sending test OTP', ['phone' => $phone, 'admin' => auth()->id()]);

        $result = $this->saei->sendOtp($phone);

        return response()->json([
            'success'         => $result['success'],
            'message'         => $result['success'] ? 'تم إرسال الرمز إلى ' . $phone : 'فشل الإرسال: ' . $result['message'],
            'verification_id' => $result['verification_id'] ?? null,
        ]);
    }

    /**
     * التحقق من الكود التجريبي عبر ساعي
     * POST /admin/whatsapp-otp-test/verify
     */
    public function verify(Request $request)
    {
        $request->validate([
            'verification_id' => ['required', 'integer'],
            'code'            => ['required', 'string', 'min:4'],
        ]);

        Log::info('[AdminOTPTest] Verifying OTP', [
            'verification_id' => $request->verification_id,
            'admin'           => auth()->id(),
        ]);

        $result = $this->saei->verifyOtp((int) $request->verification_id, $request->code);

        $statusLabel = match ($result['status'] ?? '') {
            'approved' => '✅ مقبول',
            'denied'   => '❌ مرفوض',
            'expired'  => '⏳ منتهي الصلاحية',
            default    => '⚠️ خطأ',
        };

        return response()->json([
            'success' => $result['success'] && ($result['status'] === 'approved'),
            'status'  => $result['status'] ?? 'error',
            'label'   => $statusLabel,
            'message' => $statusLabel . ' — ' . ($result['message'] ?? ''),
        ]);
    }
}
