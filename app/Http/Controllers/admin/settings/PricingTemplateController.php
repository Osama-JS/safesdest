<?php

namespace App\Http\Controllers\admin\settings;

use App\Http\Controllers\Controller;
use App\Models\Pricing_Method;
use App\Models\Pricing_Template;
use Illuminate\Http\Request;

class PricingTemplateController extends Controller
{
  public function getData(Request $request, $id)
  {
    $data = Pricing_Template::where('form_template_id', $id)->get();
    return response()->json([
      'data' => $data,
    ]);
  }

  public function getPricingMethod()
  {
    $data = Pricing_Method::where('status', 1)->get();
    return response()->json($data);
  }
}
