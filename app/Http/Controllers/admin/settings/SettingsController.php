<?php

namespace App\Http\Controllers\admin\settings;

use App\Http\Controllers\Controller;
use App\Models\Form_Template;
use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:general_settings', ['only' => ['index', 'setTemplate']]);
  }

  public function index()
  {
    $templates = Form_Template::all();
    $settings = Settings::get()->keyBy('key')->map(function ($item) {
      return [
        'value' => $item->value,
        'description' => $item->description,
      ];
    })->toArray();

    return view('admin.settings.index', compact('templates', 'settings'));
  }

  public function setTemplate(Request $req)
  {
    $req->validate([
      'key' => 'required|string',
      'value' => 'nullable|string'
    ]);

    $setting = Settings::where('key', $req->key)->first();

    if (!$setting) {
      return response()->json(['success' => false, 'message' => 'Setting not found']);
    }

    $setting->value = $req->value;
    $setting->save();

    return response()->json(['success' => true, 'message' => 'Setting updated successfully']);
  }
}
