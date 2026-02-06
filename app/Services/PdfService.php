<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\View;

class PdfService
{
    /**
     * توليد PDF من view مع البيانات
     *
     * @param string $view
     * @param array $data
     * @param string|null $filename
     * @param bool $download
     * @return \Illuminate\Http\Response
     */
    public function generate(string $view, array $data = [], ?string $filename = null, bool $download = false)
    {
        $html = View::make($view, $data)->render();
        file_put_contents(storage_path('app/mpdf-debug.html'), $html);


        // إعداد mPDF مع دعم الخط العربي
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // ✅ لا تضع orientation داخل المصفوفة
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4', // يمكنك وضع ['A4-L'] لو أردت landscape
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
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

        ]);


        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $tds = $dom->getElementsByTagName('td');
        $trs = $dom->getElementsByTagName('tr');

        Log::info('TD count: ' . $tds->length . ', TR count: ' . $trs->length);

        $chunks = str_split($html, 50000); // 50 ألف حرف لكل جزء

        if (isset($data['watermark_image']) && !empty($data['watermark_image'])) {
            $mpdf->SetWatermarkImage($data['watermark_image'], 0.1, [150, 150]);
            $mpdf->showWatermarkImage = true;
        }

        foreach ($chunks as $chunk) {
            $mpdf->WriteHTML($chunk);
        }

        $fileName = $filename ?? 'document.pdf';

        if ($download) {
            return response($mpdf->Output($fileName, 'D'))
                ->header('Content-Type', 'application/pdf');
        }

        return response($mpdf->Output($fileName, 'I'))
            ->header('Content-Type', 'application/pdf');
    }


    public function generateRaw(string $view, array $data = []): string
    {
        $html = View::make($view, $data)->render();

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
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
            'dpi' => 300,
            'img_dpi' => 300,
        ]);

        $mpdf->WriteHTML($html);

        // 🔥 S = String (محتوى PDF)
        return $mpdf->Output('', 'S');
    }

}
