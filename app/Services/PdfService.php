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

        // إعداد mPDF مع دعم الخط العربي وتحسين الأداء
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
            'simpleTables' => true,
            'useSubstitutions' => false,
            'packTableData' => true,
        ]);

        if (isset($data['watermark_image']) && !empty($data['watermark_image'])) {
            $mpdf->SetWatermarkImage($data['watermark_image'], 0.1, [150, 150]);
            $mpdf->showWatermarkImage = true;
        }

        $mpdf->WriteHTML($html);

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
