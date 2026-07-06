<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use LogsActivity;

  protected $table = 'settings';
  protected $fillable = [
    'key',
    'value',
    'description',
    'type',
    'category',
    'name',
    'options'
  ];

  protected $casts = [
    'options' => 'array'
  ];

  /**
   * الحصول على قيمة إعداد
   */
  public static function getValue($key, $default = null)
  {
    $setting = self::where('key', $key)->first();
    return $setting ? $setting->value : $default;
  }

  /**
   * تحديث أو إنشاء إعداد
   */
  public static function setValue($key, $value, $description = null)
  {
    return self::updateOrCreate(
      ['key' => $key],
      [
        'value' => $value,
        'description' => $description
      ]
    );
  }

  /**
   * الحصول على جميع الإعدادات حسب الفئة
   */
  public static function getByCategory($category)
  {
    return self::where('category', $category)->get();
  }

  /**
   * الحصول على الإعدادات كمصفوفة key => value
   */
  public static function getAsArray($category = null)
  {
    $query = self::query();

    if ($category) {
      $query->where('category', $category);
    }

    return $query->pluck('value', 'key')->toArray();
  }
}
