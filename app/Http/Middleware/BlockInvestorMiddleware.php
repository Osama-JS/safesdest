<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockInvestorMiddleware
{
    /**
     * يمنع المستثمرين من الوصول إلى لوحة تحكم الـ Admin.
     * يُضاف على route group الأدمن.
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->investor) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'المستثمرون يستخدمون لوحة التحكم الخاصة بهم'], 403);
            }
            return redirect()->route('investor.dashboard')
                ->with('info', 'يرجى استخدام لوحة تحكم المستثمر الخاصة بك.');
        }

        return $next($request);
    }
}
