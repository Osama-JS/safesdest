<?php

namespace App\Helpers;

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileHelper
{

  /**
   * تخزين الملف مع إضافة رمز عشوائي في بداية الاسم.
   *
   * @param \Illuminate\Http\UploadedFile $file
   * @param string $folder
   * @return string
   */
  public static function uploadFile($file, $folder)
  {
    // توليد رمز عشوائي
    $randomString = Str::random(10);

    // الحصول على الاسم الأصلي للملف
    $originalName = $file->getClientOriginalName();

    // إنشاء اسم الملف الجديد (رمز عشوائي - الاسم الأصلي)
    $fileName = $randomString . '-' . $originalName;

    // تخزين الملف في المسار المحدد
    $filePath = $file->storeAs($folder, $fileName, 'public');

    // إرجاع المسار الكامل للملف المخزن
    return $filePath;
  }

  /**
   * حذف الملف إذا كان موجودًا.
   *
   * @param string $filePath
   * @return void
   */
  public static function deleteFileIfExists($filePath)
  {
    if (Storage::disk('public')->exists($filePath)) {
      Storage::disk('public')->delete($filePath);
    }
  }
}
