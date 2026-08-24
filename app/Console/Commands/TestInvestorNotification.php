<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InvestorNotificationService;
use Illuminate\Console\Command;

class TestInvestorNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investor:notify-test 
                            {email? : البريد الإلكتروني للمستلم للتجربة} 
                            {--type=settlement : نوع الإشعار (settlement, investment, deposit, refund)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تجربة إرسال إشعارات محفظة الاستثمار للمستثمرين';

    /**
     * Execute the console command.
     */
    public function handle(InvestorNotificationService $service)
    {
        $email = $this->argument('email');
        $type = $this->option('type') ?? 'settlement';

        if (!$email) {
            $user = User::whereHas('investorWallet')->first() ?? User::first();
            if (!$user) {
                $this->error('لم يتم العثور على أي مستخدم في قاعدة البيانات.');
                return 1;
            }
            $email = $user->email;
        } else {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = new User([
                    'name'  => 'مستثمر تجريبي',
                    'email' => $email,
                ]);
            }
        }

        $this->info("جاري إرسال إشعار تجريبي من نوع [{$type}] إلى البريد: {$email}...");

        switch ($type) {
            case 'investment':
                $service->notifyTaskInvestment($user, 4500.00, [101, 102, 103], 25500.00, 'تجربة استثمار في 3 مهام');
                break;

            case 'deposit':
                $service->notifyDeposit($user, 10000.00, 30000.00, 'إيداع رأس مال يدوي', 'إيداع تجريبي للاختبار');
                break;

            case 'refund':
                $service->notifyRefund($user, 1500.00, 101, 27000.00, 'إلغاء واسترداد مهمة تجريبية');
                break;

            case 'settlement':
            default:
                $service->notifySettlement($user, 7850.50, [105, 108, 112], 37850.50, 'customer_payment', 'تسوية مجمعة لعدة مهام بعد سداد العميل');
                break;
        }

        $this->info("✓ تم إرسال/إدراج الإيميل في الـ Queue بنجاح!");
        $this->comment("ملاحظة: إذا كنت تستخدم queue=database في الـ Local، قم بتشغيل 'php artisan queue:work' لمعالجة الإيميل.");

        return 0;
    }
}
