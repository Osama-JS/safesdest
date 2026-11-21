<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = App\Models\InvestorWalletTransaction::whereNotNull('attachment')->latest()->first() 
      ?? App\Models\InvestorWalletTransaction::latest()->first();

echo "Testing with transaction ID: " . $tx->id . " (has attachment: " . ($tx->attachment ? 'yes' : 'no') . ")\n";

$wallet = $tx->wallet;
$user = $wallet->investor;
$performer = $tx->performer;
$printerUser = $performer;

$controller = new App\Http\Controllers\admin\InvestorWalletsController(app(App\Services\PdfService::class));
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('convertNumberToArabicWords');
$method->setAccessible(true);
$amountInWords = $method->invoke($controller, $tx->amount);

$html = Illuminate\Support\Facades\View::make('admin.investors.wallets.receipt_pdf', [
    'transaction' => $tx,
    'wallet' => $wallet,
    'user' => $user,
    'performer' => $performer,
    'printerUser' => $printerUser,
    'amountInWords' => $amountInWords
])->render();

$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];
$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'autoScriptToLang' => true,
    'autoLangToFont' => true,
    'fontDir' => array_merge($fontDirs, [public_path('fonts')]),
    'fontdata' => $fontData + [
        'tajawal' => [
            'R' => 'Tajawal-Regular.ttf',
            'B' => 'Tajawal-Bold.ttf',
        ],
    ],
    'default_font' => 'tajawal',
    'tempDir' => storage_path('app/mpdf-temp'),
    'simpleTables' => true,
    'useSubstitutions' => false,
    'packTableData' => true,
]);

$mpdf->WriteHTML($html);
echo "Page count: " . $mpdf->page . "\n";
