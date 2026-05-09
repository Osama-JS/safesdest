<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        // Redirect based on user type and guard
        if (Auth::guard('driver')->check()) {
            return redirect()->intended(route('driver.dashboard'));
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->intended(route('customer.dashboard'));
        }

        // Check if the user is an investor
        if ($user && $user->investor) {
            return redirect()->intended(route('investor.dashboard'));
        }

        // Default admin/staff redirect
        return redirect()->intended(route('user.dashboard'));
    }
}
