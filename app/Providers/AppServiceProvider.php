<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;


class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    // كود Vite كما هو
    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src)
            ? 'template-customizer-core-css'
            : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src)
              ? 'template-customizer-theme-css'
              : ''),
        ];
      }
      return [];
    });

    // قراءة ترجمة JSON بدل ملفات PHP
    $locale = App::currentLocale();


    $langFile = base_path("lang/{$locale}.json");
    $translations = [];

    if (File::exists($langFile)) {
      $translations = json_decode(File::get($langFile), true);
    }

    View::share('jsTranslations', json_encode($translations));
  }
}
