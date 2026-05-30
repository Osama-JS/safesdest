<?php

namespace App\Http\Controllers\investor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InvestorProfileController extends Controller
{
    public function show()
    {
        $investor = auth()->user()->load('activeInvestmentContract');
        return view('investor.profile.show', compact('investor'));
    }

    public function update(Request $request)
    {
        $investor = auth()->user();

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'phone_code' => 'nullable|string|max:10',
            'email'      => 'required|email|unique:users,email,' . $investor->id,
        ]);

        $investor->update($data);

        return back()->with('success', __('Profile updated successfully'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $investor = auth()->user();

        if (!Hash::check($request->current_password, $investor->password)) {
            return back()->withErrors(['current_password' => __('Current password incorrect')]);
        }

        $investor->update(['password' => Hash::make($request->password)]);

        return back()->with('success', __('Password changed successfully'));
    }
}
