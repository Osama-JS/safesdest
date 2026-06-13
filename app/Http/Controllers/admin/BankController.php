<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index()
    {
        $banks = Bank::all();
        return view('admin.banks.index', compact('banks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        Bank::create([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->has('is_active') ? true : false
        ]);

        return redirect()->route('admin.banks.index')->with('success', __('Bank added successfully.'));
    }

    public function update(Request $request, Bank $bank)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $bank->update([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->has('is_active') ? true : false
        ]);

        return redirect()->route('admin.banks.index')->with('success', __('Bank updated successfully.'));
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return redirect()->route('admin.banks.index')->with('success', __('Bank deleted successfully.'));
    }
}
