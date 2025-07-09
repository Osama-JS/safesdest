<?php

namespace App\Http\Controllers\customer;

use App\Models\Task;
use App\Models\Driver;
use App\Jobs\DistributeTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use App\Models\Customer;
use App\Models\Form_Field;
use App\Models\Settings;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
  public function index()
  {
    $vehicles = Vehicle::all();
    $task_template = Settings::where('key', 'task_template')->first();
    $task_from_template = Settings::where('key', 'task_from_port_template')->first();
    $task_to_template = Settings::where('key', 'task_to_port_template')->first();

    $template_fields = Form_Field::where('form_template_id', $task_template->value)->get();
    $template_from_fields = Form_Field::where('form_template_id', $task_from_template->value)->get();
    $template_to_fields = Form_Field::where('form_template_id', $task_to_template->value)->get();

    return view('customers.index', compact('vehicles', 'template_fields', 'task_template', 'task_from_template', 'task_to_template', 'template_from_fields', 'template_to_fields'));
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

  public function getTasks(Request $request)
  {
    $perPage = $request->get('per_page', 6);
    $page = $request->get('page', 1);
    $search = $request->get('search', '');
    $status = $request->get('status', '');
    $date = $request->get('date', '');

    $query = Task::with('driver', 'pickup', 'delivery')
      ->where('customer_id', Auth::user()->id);

    // Apply search filter
    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhereHas('pickup', function ($pickup) use ($search) {
            $pickup->where('address', 'LIKE', "%{$search}%")
              ->orWhere('contact_name', 'LIKE', "%{$search}%");
          })
          ->orWhereHas('delivery', function ($delivery) use ($search) {
            $delivery->where('address', 'LIKE', "%{$search}%")
              ->orWhere('contact_name', 'LIKE', "%{$search}%");
          })
          ->orWhereHas('driver', function ($driver) use ($search) {
            $driver->where('name', 'LIKE', "%{$search}%");
          });
      });
    }

    // Apply status filter
    if (!empty($status)) {
      $query->where('status', $status);
    }

    // Apply date filter
    if (!empty($date)) {
      $query->whereDate('created_at', $date);
    }

    // Order by latest first
    $query->orderBy('created_at', 'desc');

    // Get paginated results
    $tasks = $query->paginate($perPage, ['*'], 'page', $page);

    // Transform pagination data
    $pagination = [
      'current_page' => $tasks->currentPage(),
      'last_page' => $tasks->lastPage(),
      'per_page' => $tasks->perPage(),
      'total' => $tasks->total(),
      'from' => $tasks->firstItem(),
      'to' => $tasks->lastItem(),
      'has_more_pages' => $tasks->hasMorePages()
    ];

    return response()->json([
      'data' => $tasks->items(),
      'pagination' => $pagination
    ]);
  }
}
