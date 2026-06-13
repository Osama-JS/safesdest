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
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'is_active' => 'nullable'
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 0, 'error' => $validator->errors()]);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Bank::create([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->has('is_active') ? true : false
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 1, 'success' => __('Bank added successfully.')]);
        }

        return redirect()->route('admin.banks.index')->with('success', __('Bank added successfully.'));
    }

    public function update(Request $request, Bank $bank)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'is_active' => 'nullable'
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 0, 'error' => $validator->errors()]);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $bank->update([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->has('is_active') ? true : false
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 1, 'success' => __('Bank updated successfully.')]);
        }

        return redirect()->route('admin.banks.index')->with('success', __('Bank updated successfully.'));
    }

    public function destroy(Request $request, Bank $bank)
    {
        $bank->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 1, 'success' => __('Bank deleted successfully.')]);
        }

        return redirect()->route('admin.banks.index')->with('success', __('Bank deleted successfully.'));
    }
}
