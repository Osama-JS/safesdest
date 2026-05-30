<?php

/**
 * One-time script: wrap known Arabic investor UI strings with __() in blade files.
 * Run: php lang/apply_investor_views.php
 */

$replacements = [
    "'إدارة المضاربين'" => "__('Investor Management')",
    "'>إجمالي المضاربين<'" => ">' . __('Total Investors') . '<",
    ">إجمالي المضاربين<" => ">{{ __('Total Investors') }}<",
    ">المضاربون النشطون<" => ">{{ __('Active Investors') }}<",
    ">مضاربة بالمهام<" => ">{{ __('Task-based Investment') }}<",
    ">مضاربة عامة<" => ">{{ __('General Investment') }}<",
    ">قائمة المضاربين<" => ">{{ __('Investors List') }}<",
    "> إضافة مضارب جديد<" => "> {{ __('Add New Investor') }}<",
    ">المضارب<" => ">{{ __('Investor') }}<",
    ">البريد الإلكتروني<" => ">{{ __('Email') }}<",
    ">رصيد المحفظة<" => ">{{ __('Wallet Balance') }}<",
    ">نوع العقد<" => ">{{ __('Contract Type') }}<",
    ">العمولة<" => ">{{ __('Commission') }}<",
    ">الحالة<" => ">{{ __('Status') }}<",
    ">إعادة تعيين كلمة المرور<" => ">{{ __('Reset Password') }}<",
    ">الإجراءات<" => ">{{ __('Actions') }}<",
    "'>إضافة مضارب جديد<'" => ">' . __('Add New Investor') . '<",
    ">إضافة مضارب جديد<" => ">{{ __('Add New Investor') }}<",
    ">تفاصيل المضارب<" => ">{{ __('Investor Details') }}<",
    ">ربط المهام التاريخية<" => ">{{ __('Link Historical Tasks') }}<",
    "'لوحة تحكم المضارب'" => "__('Investor Dashboard')",
    "'المحفظة الشخصية - العمولات'" => "__('Commission Wallet - Profits')",
    "'محفظة المضاربة'" => "__('Investment Wallet')",
    "'تمويل المهام'" => "__('Task Funding')",
    "'الملف الشخصي'" => "__('Profile')",
    ">محفظة العمولات<" => ">{{ __('Commission Wallet') }}<",
    ">محفظة المضاربة<" => ">{{ __('Investment Wallet') }}<",
    ">الرئيسية<" => ">{{ __('Home') }}<",
    "> استثمار الأرباح<" => "> {{ __('Reinvest Profits') }}<",
    "> احتساب العمولات الآن<" => "> {{ __('Calculate Commissions Now') }}<",
    ">الرصيد القابل للسحب<" => ">{{ __('Withdrawable Balance') }}<",
    ">رصيد محفظة المضاربة<" => ">{{ __('Investment Wallet Balance') }}<",
    ">نوع عقد المضاربة<" => ">{{ __('Investment Contract Type') }}<",
    "> استثمار الأرباح<" => "> {{ __('Reinvest Profits') }}<",
    ">عرض محفظة المضاربة<" => ">{{ __('View Investment Wallet') }}<",
    ">لم تُنشأ محفظة مضاربة بعد<" => ">{{ __('Investment wallet not created yet') }}<",
    "'مضاربة بالمهام'" => "__('Task-based investment')",
    "'مضاربة عامة'" => "__('General investment')",
];

$files = [
    __DIR__ . '/../resources/views/admin/investors/index.blade.php',
    __DIR__ . '/../resources/views/investor/dashboard.blade.php',
    __DIR__ . '/../resources/views/investor/personal-wallet/index.blade.php',
    __DIR__ . '/../resources/views/investor/investment-wallet/index.blade.php',
    __DIR__ . '/../resources/views/investor/task-payment/index.blade.php',
    __DIR__ . '/../resources/views/investor/paid-tasks/index.blade.php',
    __DIR__ . '/../resources/views/investor/profile/show.blade.php',
    __DIR__ . '/../resources/views/admin/investors/wallets/invest.blade.php',
    __DIR__ . '/../resources/views/admin/user-wallets/show.blade.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Skip missing: $file\n";
        continue;
    }
    $content = file_get_contents($file);
    $original = $content;
    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    }
}

echo "Done.\n";
