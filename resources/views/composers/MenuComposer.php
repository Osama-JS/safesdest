<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class MenuComposer
{
  public function compose(View $view)
  {
    $guard = trim(Session::get('guard'));

    switch ($guard) {
      case 'driver':
        $menuKey = 'vertical_menu_driver';
        $menuFile = base_path('resources/menu/verticalDriverMenu.json');
        break;
      case 'customer':
        $menuKey = 'vertical_menu_customer';
        $menuFile = base_path('resources/menu/verticalCustomerMenu.json');
        break;
      case 'web':
        $menuKey = 'vertical_menu_web';
        $menuFile = base_path('resources/menu/verticalMenu.json');
        break;
      default:
        $menuKey = 'vertical_menu_default';
        $menuFile = base_path('resources/menu/defaultMenu.json');
        break;
    }

    // Cache vertical menu for 60 minutes
    $verticalMenuData = Cache::remember($menuKey, 60 * 60, function () use ($menuFile) {
      return json_decode(file_exists($menuFile) ? file_get_contents($menuFile) : '[]');
    });

    // Cache horizontal menu globally
    $horizontalMenuData = Cache::remember('horizontal_menu', 60 * 60, function () {
      $file = base_path('resources/menu/horizontalMenu.json');
      return json_decode(file_exists($file) ? file_get_contents($file) : '[]');
    });

    $view->with('menuData', [$verticalMenuData, $horizontalMenuData]);
  }
}
