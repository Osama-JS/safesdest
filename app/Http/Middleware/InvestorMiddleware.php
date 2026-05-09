<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InvestorMiddleware
{
    /**
     * يتحقق من أن المستخدم الحالي مستثمر.
     * يحمي routes لوحة تحكم المستثمر.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth('web')->check() || !auth('web')->user()->investor) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'غير مصرح بالوصول'], 403);
            }
            return redirect()->route('login')->with('error', 'هذه الصفحة مخصصة للمستثمرين فقط.');
        }

        return $next($request);
    }
}
