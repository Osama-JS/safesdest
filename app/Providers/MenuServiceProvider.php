<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;

use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // استخدام View Composer لتحديد القائمة حسب الـ guard
    View::composer('layouts.sections.menu.verticalMenu', function ($view) {
      // تحديد الـ guard الحالي
      $currentGuard = 'web'; // default
      foreach (['driver', 'customer', 'web'] as $g) {
        if (auth($g)->check()) {
          $currentGuard = $g;
          // تخصيص قائمة المستثمر
          if ($g === 'web' && auth($g)->user()->investor) {
            $currentGuard = 'investor';
          }
          break;
        }
      }

      // تحميل القوائم للـ guard الحالي
      $verticalPath = base_path("resources/menu/{$currentGuard}/verticalMenu.json");
      $verticalData = file_exists($verticalPath) ? json_decode(file_get_contents($verticalPath)) : null;

      // تحميل القائمة الأفقية العامة
      $horizontalPath = base_path("resources/menu/horizontalMenu.json");
      $horizontalData = file_exists($horizontalPath) ? json_decode(file_get_contents($horizontalPath)) : null;

      // تمرير البيانات بالشكل المتوقع
      $menuData = [$verticalData, $horizontalData];
      $view->with('menuData', $menuData);
    });

    // View Composer للقائمة الأفقية
    View::composer('layouts.sections.menu.horizontalMenu', function ($view) {
      $horizontalPath = base_path("resources/menu/horizontalMenu.json");
      $horizontalData = file_exists($horizontalPath) ? json_decode(file_get_contents($horizontalPath)) : null;

      $menuData = [null, $horizontalData];
      $view->with('menuData', $menuData);
    });
  }
}
