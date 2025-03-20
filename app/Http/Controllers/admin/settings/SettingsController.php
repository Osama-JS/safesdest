<?php

namespace App\Http\Controllers\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{

  public function index()
  {
    $pageConfigs = ['myLayout' => 'vertical'];

    return view('admin.settings.index', ['pageConfigs' => $pageConfigs]);
  }
}
