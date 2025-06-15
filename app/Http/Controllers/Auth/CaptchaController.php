<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    public function refresh()
    {
        return response()->json(['captcha' => captcha_src()]);
    }
}
