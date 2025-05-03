<?php

namespace App\Http\Controllers\Auth;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Email_Verification_Resends;
use App\Models\Email_Verifications;
use App\Models\Form_Field;
use App\Models\Form_Template;
use App\Models\Settings;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
  public function index()
  {
    $vehicles = Vehicle::all();
    $customer = Settings::where('key', 'customer_template')->first();
    $driver = Settings::where('key', 'driver_template')->first();

    $customer_template = Form_Field::where('form_template_id', $customer->value)->get();
    $driver_template = Form_Field::where('form_template_id', $driver->value)->get();


    return view('auth.register', compact('vehicles', 'customer_template', 'driver_template', 'customer', 'driver'));
  }

  function createVerificationToken($user)
  {
    $token = Str::random(64);
    Email_Verifications::insert([
      'verifiable_id' => $user->id,
      'verifiable_type' => get_class($user),
      'token' => $token,
      'created_at' => now(),
    ]);
    return $token;
  }

  function sendVerificationEmail($user, $type)
  {
    $token = ($this)->createVerificationToken($user);
    $verifyLink = route('verify.email', ['token' => $token]);

    Mail::send("emails.verify-account", ['user' => $user, 'verifyLink' => $verifyLink], function ($message) use ($user) {
      $message->to($user->email)->subject('Verify Your Email');
    });
  }

  public function verifyEmail($token)
  {
    $record = Email_Verifications::where('token', $token)->first();
    if (!$record || Carbon::parse($record->created_at)->addHour()->isPast()) {
      return view('auth.verify-failed');
    }
    $model = $record->verifiable_type;
    $user = $model::find($record->verifiable_id);
    if (!$user) return view('auth.verify-failed');


    $user->status = 'active';
    $user->save();

    Email_Verifications::where('token', $token)->delete();
    return view('auth.verify-success');
  }

  public function resendVerification(Request $req)
  {
    $req->validate(['email' => 'required|email']);

    $customer = Customer::where('email', $req->email)->first();
    $driver = Driver::where('email', $req->email)->first();

    $user = $customer ?? $driver;
    $type = $customer ? 'customer' : ($driver ? 'driver' : null);

    if (!$user) {
      return back()->withErrors(['email' => 'User not found.']);
    }

    if ($user->status !== 'verified') {
      return back()->withErrors(['email' => 'Your email is already verified.']);
    }

    $ip = $req->ip();
    $email = $req->email;

    $resend = Email_Verification_Resends::where('email', $email)->first();

    // تحقق من عدد المحاولات اليومية
    if ($resend && $resend->resend_count >= 3 && Carbon::parse($resend->last_sent_at)->isToday()) {
      return back()->withErrors(['email' => 'You have reached the maximum resend attempts for today. Try again tomorrow.']);
    }

    // تحقق من وجود مهلة زمنية (Cooldown) بين المحاولات
    if ($resend && Carbon::parse($resend->last_sent_at)->diffInMinutes(now()) < 2) {
      return back()->withErrors(['email' => 'Please wait a few minutes before resending verification email.']);
    }

    // أرسل رسالة التحقق
    $this->sendVerificationEmail($user, $type);

    // تحديث السجل أو إنشاؤه
    if ($resend) {
      Email_Verification_Resends::where('email', $email)->update([
        'resend_count' => Carbon::parse($resend->last_sent_at)->isToday() ? $resend->resend_count + 1 : 1,
        'last_sent_at' => now(),
        'ip_address' => $ip,
        'updated_at' => now(),
      ]);
    } else {
      Email_Verification_Resends::insert([
        'email' => $email,
        'resend_count' => 1,
        'last_sent_at' => now(),
        'ip_address' => $ip,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    $remaining = 3;

    if ($resend) {
      $remaining = Carbon::parse($resend->last_sent_at)->isToday()
        ? max(0, 3 - $resend->resend_count)
        : 3;
    }

    return back()->with([
      'status' => 'Verification email resent successfully.',
      'remaining_attempts' => $remaining
    ]);
  }




  public function registerCustomer(Request $req)
  {

    $validator = Validator::make($req->all(), [
      'name'           => 'required|string|max:255',
      'email'          => 'required|email|unique:customers,email',
      'phone'          => 'required|unique:customers,phone',
      'phone_code'     => 'required|string',
      'password'       => 'required|same:confirm-password',
      'c_name'         => 'nullable|string|max:255',
      'c_address'      => 'nullable|string|max:255',
    ]);

    if ($req->filled('template')) {
      $fields = Form_Field::where('form_template_id', $req->template)->get();
      foreach ($fields as $key) {
        if ($key->required) {
          $rules['additional_fields.' . $key->name] = 'required';
        }
      }
    }

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }
    DB::beginTransaction();


    try {

      $data = [
        'name'            => $req->name,
        'email'           => $req->email,
        'phone'           => $req->phone,
        'phone_code'      => $req->phone_code,
        'password'        => Hash::make($req->password),
        'company_name'    => $req->phone_code,
        'company_address' => $req->c_address,
      ];

      $structuredFields = [];

      if ($req->filled('template')) {
        $data['form_template_id'] = $req->template;

        $template = Form_Template::with('fields')->find($req->input('template'));

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          if ($req->has("additional_fields.$fieldName")) {
            $structuredFields[$fieldName] = [
              'label' => $field->label,
              'value' => $req->input("additional_fields.$fieldName"),
              'type'  => $field->type,
            ];
          }
        }
        $data['additional_data'] = $structuredFields;
      }

      // إنشاء العميل
      $customer = Customer::create($data);

      ($this)->sendVerificationEmail($customer, 'customer');

      DB::commit();
      return response()->json([
        'status'  => 1,
        'success' => __('Your Account Created successfully'),
        'url' =>  route('verify.email.sent',  $customer->email)
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error'  => $ex->getMessage()
      ]);
    }
  }

  public function registerDriver(Request $req)
  {

    $validator = Validator::make($req->all(), [
      'name'           => 'required|string|max:255',
      'username'       => 'required|unique:drivers,username',
      'email'          => 'required|email|unique:drivers,email',
      'phone'          => 'required|unique:drivers,phone',
      'phone_code'     => 'required|string',
      'password'       => 'required|same:confirm-password',
      'address'        => 'required|string|max:255',
      'vehicle'        => 'nullable|string|max:255',
    ]);

    if ($req->filled('template')) {
      $fields = Form_Field::where('form_template_id', $req->template)->get();
      foreach ($fields as $key) {
        if ($key->required) {
          $rules['additional_fields.' . $key->name] = 'required';
        }
      }
    }
    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    DB::beginTransaction();

    try {
      $data = [
        'name' => $req->name,
        'username' => $req->username,
        'email' => $req->email,
        'phone' => $req->phone,
        'phone_code' => $req->phone_code,
        'password' => Hash::make($req->password),
        'address' => $req->address,
        'vehicle_size_id' => $req->vehicle,
      ];

      $structuredFields = [];

      if ($req->filled('template')) {
        $data['form_template_id'] = $req->template;

        $template = Form_Template::with('fields')->find($req->input('template'));

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          if ($req->has("additional_fields.$fieldName")) {
            $structuredFields[$fieldName] = [
              'label' => $field->label,
              'value' => $req->input("additional_fields.$fieldName"),
              'type'  => $field->type,
            ];
          }
        }
        $data['additional_data'] = $structuredFields;
      }



      $driver = Driver::create($data);

      ($this)->sendVerificationEmail($driver, 'driver');



      DB::commit();
      return response()->json([
        'status'  => 1,
        'success' => 'Your Account Created successfully',
        'url' =>  route('verify.email.sent',  $driver->email)
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error'  => $ex->getMessage()
      ]);
    }
  }



  public function logout(Request $request)
  {
    $guard = $request->session()->get('guard', 'web');

    Auth::guard($guard)->logout();

    if ($request->hasSession()) {
      $request->session()->invalidate();
      $request->session()->regenerateToken();
    }

    return redirect()->route('login')->with('status', 'You have been logged out.');
  }

  protected function getModel($type)
  {
    return match ($type) {
      'driver' => Driver::class,
      'customer' => Customer::class,
    };
  }


  public function showRequestForm()
  {
    return view('auth.forgot-password');
  }

  public function sendResetLink(Request $request)
  {
    $request->validate([
      'email' => 'required|email',
      'type' => 'required|in:driver,customer'
    ]);

    $model = $this->getModel($request->type);
    $user = $model::where('email', $request->email)->first();

    if (!$user) {
      return back()->withErrors(['email' => 'No account found with this email.']);
    }

    $token = Str::random(64);
    DB::table('password_resets')->updateOrInsert(
      ['email' => $user->email, 'type' => $request->type],
      ['token' => hash('sha256', $token), 'created_at' => now()]
    );

    $resetLink = route('password.reset.form', [
      'token' => $token,
      'email' => $user->email,
      'type' => $request->type
    ]);

    Mail::send('emails.password-reset', [
      'url' => $resetLink,
      'name' => $user->name ?? $user->email
    ], function ($message) use ($user) {
      $message->to($user->email)->subject('Reset Your Password');
    });

    return back()->with('status', 'Password reset link sent successfully!');
  }

  public function showResetForm(Request $request)
  {
    return view('auth.reset-password', [
      'token' => $request->token,
      'email' => $request->email,
      'type' => $request->type
    ]);
  }

  public function updatePassword(Request $request)
  {
    $request->validate([
      'email' => 'required|email',
      'type' => 'required|in:user,driver,customer',
      'token' => 'required',
      'password' => 'required|confirmed|min:8'
    ]);

    $reset = DB::table('password_resets')
      ->where('email', $request->email)
      ->where('type', $request->type)
      ->where('token', hash('sha256', $request->token))
      ->first();

    if (!$reset || Carbon::parse($reset->created_at)->addMinutes(30)->isPast()) {
      return back()->withErrors(['email' => 'Invalid or expired token.']);
    }

    $model = $this->getModel($request->type);
    $user = $model::where('email', $request->email)->first();

    $user->password = Hash::make($request->password);
    $user->save();

    DB::table('password_resets')
      ->where('email', $request->email)
      ->where('type', $request->type)
      ->delete();

    return redirect()->route('login')->with('status', 'Your password has been updated!');
  }
}
