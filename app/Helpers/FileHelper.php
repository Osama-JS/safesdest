<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class FileHelper
{
    /**
     * قائمة الامتدادات التنفيذية والخطيرة الممنوعة منعاً باتاً لحماية الخادم من هجمات RCE و Webshells
     */
    protected static array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'inc',
        'sh', 'bash', 'zsh', 'exe', 'bat', 'cmd', 'bin', 'msi', 'com', 'scr',
        'js', 'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'psm1',
        'html', 'htm', 'xhtml', 'shtml', 'pl', 'py', 'cgi',
        'asp', 'aspx', 'jsp', 'jspx', 'htaccess', 'htpasswd', 'ini', 'env'
    ];

    /**
     * تخزين الملف بشكل آمن مع توليد اسم مشفر عشوائي وفحص الامتداد وصلاحية الملف.
     *
     * @param \Illuminate\Http\UploadedFile|mixed $file
     * @param string $folder
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function uploadFile($file, $folder)
    {
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new InvalidArgumentException(__('الملف المرفوع غير صالح أو تالف.'));
        }

        // 1. استخراج وتطهير الامتداد
        $originalExt = strtolower($file->getClientOriginalExtension());
        $guessedExt = strtolower($file->guessExtension() ?? '');
        $extension = $originalExt ?: $guessedExt;

        // تنظيف الامتداد من أي محارف غير ألفانوية
        $extension = preg_replace('/[^a-z0-9]/', '', $extension);

        // 2. الحماية من الامتدادات الخطيرة والتنفيذية
        if (empty($extension) || in_array($extension, self::$dangerousExtensions, true) || in_array($guessedExt, self::$dangerousExtensions, true)) {
            throw new InvalidArgumentException(__('نوع أو امتداد الملف المرفوع غير مسموح به لأسباب أمنية.'));
        }

        // 3. تطهير مسار المجلد لمنع Directory Traversal
        $safeFolder = self::sanitizePath($folder);

        // 4. توليد اسم عشوائي آمن كلياً (UUID v4) لمنع التحكم بأسماء الملفات واستغلال ثغرات التسمية
        $fileName = Str::uuid()->toString() . '.' . $extension;

        // 5. تخزين الملف في القرص العام (public disk)
        $filePath = $file->storeAs($safeFolder, $fileName, 'public');

        // إرجاع المسار الكامل للملف المخزن
        return $filePath;
    }

    /**
     * حذف الملف إذا كان موجودًا مع تطهير المسار.
     *
     * @param string|null $filePath
     * @return void
     */
    public static function deleteFileIfExists($filePath)
    {
        if (empty($filePath) || !is_string($filePath)) {
            return;
        }

        $safePath = self::sanitizePath($filePath);

        if (Storage::disk('public')->exists($safePath)) {
            Storage::disk('public')->delete($safePath);
        }
    }

    /**
     * نسخ ملف موجود إلى مجلد وجهة آمن مع توليد اسم فريد.
     *
     * @param string $originalPath
     * @param string $destinationDir
     * @return string|null
     */
    public static function duplicateFile($originalPath, $destinationDir)
    {
        $safeOriginalPath = self::sanitizePath($originalPath);
        $safeDestinationDir = self::sanitizePath($destinationDir);

        if (!Storage::disk('public')->exists($safeOriginalPath)) {
            return null;
        }

        $extension = pathinfo($safeOriginalPath, PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-z0-9]/i', '', strtolower($extension));

        if (in_array($extension, self::$dangerousExtensions, true)) {
            return null;
        }

        $newFileName = Str::uuid()->toString() . ($extension ? '.' . $extension : '');
        $newPath = trim($safeDestinationDir, '/') . '/' . $newFileName;

        Storage::disk('public')->copy($safeOriginalPath, $newPath);
        return $newPath;
    }

    /**
     * تطهير المسارات لمنع هجمات التراجع في المسار (Directory Traversal) والرموز الخبيثة
     *
     * @param string $path
     * @return string
     */
    public static function sanitizePath(string $path): string
    {
        // استبدال الشرطات المائلة العكسية
        $path = str_replace('\\', '/', $path);
        // إزالة محاولات التراجع في المسار والـ null bytes
        $path = str_replace(["\0", '%00'], '', $path);

        // تفكيك المسار وتصفية الأجزاء الخطرة
        $parts = explode('/', $path);
        $safeParts = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed === '' || $trimmed === '.' || $trimmed === '..') {
                continue;
            }
            // إبقاء المحارف الآمنة فقط
            $clean = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $trimmed);
            if ($clean !== '' && $clean !== '.' && $clean !== '..') {
                $safeParts[] = $clean;
            }
        }

        return implode('/', $safeParts);
    }
}
