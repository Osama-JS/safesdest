<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;

class IpHelper
{
  /**
   * دالة لاستخراج عنوان الـ IP الخاص بالمستخدم
   *
   * @return string
   */
  public static function getUserIpAddress()
  {
    // الحصول على الـ IP من الـ headers إذا كان موجودًا
    $ip = Request::ip();

    // في حال كان الـ IP خلف بروكسي، يتم استخدام الـ X-Forwarded-For
    if ($forwardedFor = Request::header('X-Forwarded-For')) {
      $ip = explode(',', $forwardedFor)[0];  // في حال وجود عدة عناوين IP نقوم بأخذ الأول
    }

    return $ip;
  }
}
