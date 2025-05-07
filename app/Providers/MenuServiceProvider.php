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
    $guards = ['driver', 'customer', 'web'];
    $menus = [];

    foreach ($guards as $guard) {
      $verticalPath = base_path("resources/menu/{$guard}/verticalMenu.json");
      $horizontalPath = base_path("resources/menu/{$guard}/horizontalMenu.json");

      $menus[$guard] = [
        'vertical' => file_exists($verticalPath) ? json_decode(file_get_contents($verticalPath)) : null,
        'horizontal' => file_exists($horizontalPath) ? json_decode(file_get_contents($horizontalPath)) : null,
      ];
    }

    // مشاركة كل القوائم مع جميع الـ views
    $this->app->make('view')->share('menuData', $menus);
  }
}
