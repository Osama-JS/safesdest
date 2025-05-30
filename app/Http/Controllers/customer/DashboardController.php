<?php

namespace App\Http\Controllers\customer;

use App\Models\Task;
use App\Models\Driver;
use App\Jobs\DistributeTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
  public function index()
  {
    return view('customers.index');
  }

  public function profile()
  {
    $data = Customer::find(Auth::user()->id);
    return view('customers.profile.index', compact('data'));
  }

  public function updateProfile(Request $req)
  {

    $validator = Validator::make($req->all(), [
      'name'         => 'required|string',
      'c_name'         => 'required|string',
      'c_address'      => 'required|string',
      'phone'        => 'required|unique:users,phone,' . Auth::id(),
      'phone_code'   => 'required|string',
      'password'     => 'nullable|same:confirm-password',
    ], [
      'name.required'        => __('The name field is required.'),
      'c_name.required'       => __('The Company name field is required.'),
      'c_name.string'         => __('The Company name field is required.'),
      'c_address.required'       => __('The Company address field is required.'),
      'c_address.string'         => __('The Company address field is required.'),
      'phone.required'       => __('The phone field is required.'),
      'phone.unique'         => __('The phone has already been taken.'),
      'phone_code.required'  => __('The phone code is required.'),
      'phone_code.string'    => __('The phone code must be a string.'),
      'password.same'        => __('The password and confirmation must match.'),
    ]);



    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()]);
    }
    try {
      $find = Customer::findOrFail(Auth::user()->id);
      $password =  $find->password;
      if ($req->filled('password')) {
        $password = Hash::make($req->password);
      }
      $image = $find->image;
      $oldImage = null;
      if ($req->hasFile('image')) {
        $image = (new FunctionsController)->convert($req->image, 'customers');
        $oldImage = $find->image;
      }

      $done = $find->update([
        'name' => $req->name,
        'company_name' => $req->c_name,
        'company_address' => $req->c_address,
        'image'   => $image,
        'password' => $password,
        'phone' => $req->phone,
        'phone_code' => $req->phone_code,
      ]);

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error to Update Profile')]);
      }
      if ($oldImage && $req->hasFile('image')) {
        dd('why');
        unlink($oldImage);
      }
      return response()->json(['status' => 1, 'success' => __('Profile Updated Successfully')]);
    } catch (\Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
