<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureUserIsAuthenticatedWithCorrectGuard
{
  public function handle($request, Closure $next, $guard)
  {
    // اجبر Laravel أن يستخدم الـ Guard الصحيح
    Auth::shouldUse($guard);

    // تحقق هل المستخدم مسجل دخول بنفس الـ Guard
    if (!Auth::guard($guard)->check()) {
      // تسجيل خروج من جميع الجلسات
      throw new NotFoundHttpException(); // بدلاً من 401، نرمي 404
    }

    // تحقق أن نوع الحارس في الجلسة نفس الـ Guard الحالي
    if (Session::get('guard') !== $guard) {
      // Auth::logout();
      // $request->session()->invalidate();
      // $request->session()->regenerateToken();

      // return redirect()->route('login')->withErrors([
      //   'email' => 'Invalid session guard detected. Please login again.',
      // ]);
      throw new NotFoundHttpException(); // بدلاً من 401، نرمي 404

    }

    return $next($request);
  }
}
