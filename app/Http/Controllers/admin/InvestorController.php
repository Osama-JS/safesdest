<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\InvestmentContract;
use App\Models\InvestorWallet;
use App\Models\Customer;
use App\Models\Task;
use App\Models\InvestorWalletTransaction;
use App\Models\UserWalletTransaction;
use App\Models\Form_Template;
use App\Models\Form_Field;
use App\Models\Settings;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class InvestorController extends Controller
{
  public function __construct()
  {
    $this->middleware('permission:view_investors', ['only' => ['index', 'getData', 'show']]);
    $this->middleware('permission:save_investors', ['only' => ['store']]);
    $this->middleware('permission:delete_investors', ['only' => ['destroy']]);
  }

  public function index()
  {
    $customers = Customer::where('status', 'active')->get();
    $templates = Form_Template::all();
    $investor_template = Settings::where('key', 'investor_template')->first();
    $users = User::where('status', 'active')
      ->where(function($q) {
        $q->where('investor', '!=', 1)->orWhereNull('investor');
      })
      ->orderBy('name')
      ->get();
    return view('admin.investors.index', compact('customers', 'templates', 'investor_template', 'users'));
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'email',
      4 => 'phone',
      5 => 'status',
    ];

    $query = User::where('investor', true)->where('status', '!=', 'deleted');

    $totalData = $query->count();
    $totalFiltered = $totalData;

    $limit = $request->input('length');
    $start = $request->input('start');
    $order = $columns[$request->input('order.0.column')] ?? 'id';
    $dir = $request->input('order.0.dir') ?? 'desc';

    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('name', 'LIKE', "%{$search}%")
          ->orWhere('email', 'LIKE', "%{$search}%")
          ->orWhere('phone', 'LIKE', "%{$search}%");
      });
      $totalFiltered = $query->count();
    }

    $investors = $query->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->with(['activeInvestmentContract', 'investorWallet'])
      ->get();

    $data = [];
    $ids = $start;

    foreach ($investors as $investor) {
      $contract = $investor->activeInvestmentContract;
      $nestedData['id'] = $investor->id;
      $nestedData['fake_id'] = ++$ids;
      $nestedData['name'] = $investor->name;
      $nestedData['email'] = $investor->email;
      $nestedData['phone'] = $investor->phone;
      $nestedData['status'] = $investor->status;
      $nestedData['reset_password'] = $investor->reset_password;
      $nestedData['wallet_balance'] = $investor->investorWallet->balance ?? 0;
      $nestedData['contract_type'] = $contract
        ? ($contract->contract_type == 'task_investment' ? __('Task-based') : __('General'))
        : __('No contract');
      $nestedData['raw_contract_type'] = $contract ? $contract->contract_type : null;
      $nestedData['commission'] = $contract
        ? $contract->commission_value . ($contract->commission_type == 'percentage' ? '%' : ' ' . __('Fixed'))
        : '-';

      $data[] = $nestedData;
    }

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => intval($totalData),
      'recordsFiltered' => intval($totalFiltered),
      'data' => $data,
      'summary' => [
        'total' => User::where('investor', true)->where('status', '!=', 'deleted')->count(),
        'active' => User::where('investor', true)->where('status', 'active')->count(),
        'task_based' => InvestmentContract::where('status', 'active')->where('contract_type', 'task_investment')->count(),
        'general_based' => InvestmentContract::where('status', 'active')->where('contract_type', 'general_investment')->count(),
      ]
    ]);
  }

  public function store(Request $request)
  {
    $rules = [
      'name' => 'required|string|max:255',
      'email' => 'required|email|unique:users,email,' . ($request->id ?? 0),
      'phone' => 'nullable|string|max:20',
      'phone_code' => 'required|string|max:10',
      'password' => $request->id ? 'nullable|min:6|confirmed' : 'required|min:6|confirmed',
      'status' => 'required|in:active,inactive,pending',
      // Contract rules
      'contract_type' => $request->id ? 'nullable|in:task_investment,general_investment' : 'required|in:task_investment,general_investment',
      'commission_type' => 'required|in:percentage,fixed',
      'commission_value' => 'required|numeric|min:0',
      'start_date' => 'required|date',
      'end_date' => 'nullable|date|after_or_equal:start_date',
      'customer_ids' => 'nullable|array',
      'min_commission_threshold' => 'nullable|numeric|min:0',
      'template' => 'nullable|exists:form_templates,id',
      'broker_id' => 'nullable|exists:users,id',
      'broker_commission_source' => 'nullable|in:investor_commission,task_commission',
      'broker_commission_type' => 'nullable|in:percentage,fixed',
      'broker_commission_value' => 'nullable|numeric|min:0',
      'bank_name' => 'nullable|string|max:255',
      'custom_bank_name' => 'nullable|string|max:255',
      'account_number' => 'nullable|string|max:50',
      'iban_number' => 'nullable|string|max:50',
      'bic_code' => 'nullable|string|max:50',
      'beneficiary_name' => 'nullable|string|max:255',
      'bank_address1' => 'nullable|string|max:255',
      'bank_address2' => 'nullable|string|max:255',
      'bank_city' => 'nullable|string|max:100',
      'bank_country' => 'nullable|string|max:100',
    ];

    if ($request->filled('template')) {
      $fields = Form_Field::where('form_template_id', $request->template)->get();
      foreach ($fields as $field) {
        $fieldKey = 'additional_fields.' . $field->name;
        $rules[$fieldKey] = [];

        if (!$request->filled('id') && $field->required && !in_array($field->type, ['file_expiration_date', 'file_with_text'])) {
          $rules[$fieldKey][] = 'required';
        }

        switch ($field->type) {
          case 'text':
          case 'textarea':
          case 'string':
            $rules[$fieldKey][] = 'string';
            break;
          case 'number':
            $rules[$fieldKey][] = 'numeric';
            break;
          case 'date':
            $rules[$fieldKey][] = 'date';
            break;
          case 'file':
          case 'image':
            $rules[$fieldKey][] = $field->type == 'image' ? 'image' : 'file';
            $rules[$fieldKey][] = 'max:10240';
            break;
          case 'file_expiration_date':
            $rules[$fieldKey . '_file'] = ['nullable', 'file', 'max:10240'];
            $rules[$fieldKey . '_expiration'] = ['nullable', 'date', 'after_or_equal:today'];
            if ($field->required && !$request->filled('id')) {
              $rules[$fieldKey . '_file'][] = 'required';
              $rules[$fieldKey . '_expiration'][] = 'required';
            }
            if ($request->hasFile("additional_fields.{$field->name}_file")) {
              $rules[$fieldKey . '_expiration'][] = 'required';
            }
            break;
          case 'file_with_text':
            $rules[$fieldKey . '_file'] = ['nullable', 'file', 'max:10240'];
            $rules[$fieldKey . '_text'] = ['nullable', 'string', 'max:255'];
            if ($field->required && !$request->filled('id')) {
              $rules[$fieldKey . '_file'][] = 'required';
              $rules[$fieldKey . '_text'][] = 'required';
            }
            if ($request->hasFile("additional_fields.{$field->name}_file")) {
              $rules[$fieldKey . '_text'][] = 'required';
            }
            break;
        }
      }
    }

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()]);
    }

    DB::beginTransaction();
    try {
      $investorRole = Role::where('name', 'Investor')->first();
      if (!$investorRole) {
        // Fallback to Owner if Investor role doesn't exist for some reason
        $investorRole = Role::where('name', 'Owner')->first();
      }

      $data = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'phone_code' => $request->phone_code,
        'status' => $request->status,
        'investor' => true,
        'role_id' => $investorRole ? $investorRole->id : null,
        'bank_name' => $request->bank_name === 'other' ? $request->custom_bank_name : $request->bank_name,
        'account_number' => $request->account_number,
        'iban_number' => $request->iban_number,
        'bic_code' => $request->bic_code,
        'beneficiary_name' => $request->beneficiary_name,
        'bank_address1' => $request->bank_address1,
        'bank_address2' => $request->bank_address2,
        'bank_city' => $request->bank_city,
        'bank_country' => $request->bank_country,
      ];

      if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
      }

      $structuredFields = [];
      $oldAdditionalData = [];
      $filesToDelete = [];

      if ($request->id) {
        $investor = User::findOrFail($request->id);
        $oldAdditionalData = $investor->additional_data ?? [];
        $investor->update($data);
      } else {
        $investor = User::create($data);
        // Create wallet for new investor
        InvestorWallet::create([
          'user_id' => $investor->id,
          'balance' => 0,
          'credit' => 0,
          'debit' => 0,
        ]);
      }

      // معالجة البيانات الإضافية
      if ($request->filled('template')) {
        $investor->form_template_id = $request->template;
        $template = Form_Template::with('fields')->find($request->template);

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          $fieldType = $field->type;

          if ($fieldType === 'file_expiration_date') {
            $fileFieldName = $fieldName . '_file';
            $expirationFieldName = $fieldName . '_expiration';
            if ($request->hasFile("additional_fields.$fileFieldName")) {
              if (isset($oldAdditionalData[$fieldName]['value'])) $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              $path = FileHelper::uploadFile($request->file("additional_fields.$fileFieldName"), 'investors/files');
              $structuredFields[$fieldName] = [
                'label' => $field->label, 'value' => $path, 'expiration' => $request->input("additional_fields.$expirationFieldName"), 'type' => $fieldType
              ];
            } else if (isset($oldAdditionalData[$fieldName])) {
              $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
              if ($request->filled("additional_fields.$expirationFieldName")) $structuredFields[$fieldName]['expiration'] = $request->input("additional_fields.$expirationFieldName");
            }
          } elseif ($fieldType === 'file_with_text') {
            $fileFieldName = $fieldName . '_file';
            $textFieldName = $fieldName . '_text';
            if ($request->hasFile("additional_fields.$fileFieldName")) {
              if (isset($oldAdditionalData[$fieldName]['value'])) $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              $path = FileHelper::uploadFile($request->file("additional_fields.$fileFieldName"), 'investors/files');
              $structuredFields[$fieldName] = [
                'label' => $field->label, 'value' => $path, 'text' => $request->input("additional_fields.$textFieldName"), 'type' => $fieldType
              ];
            } else if (isset($oldAdditionalData[$fieldName])) {
              $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
              if ($request->filled("additional_fields.$textFieldName")) $structuredFields[$fieldName]['text'] = $request->input("additional_fields.$textFieldName");
            }
          } elseif (in_array($fieldType, ['file', 'image'])) {
            if ($request->hasFile("additional_fields.$fieldName")) {
              if (isset($oldAdditionalData[$fieldName]['value'])) $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              $path = FileHelper::uploadFile($request->file("additional_fields.$fieldName"), 'investors/files');
              $structuredFields[$fieldName] = ['label' => $field->label, 'value' => $path, 'type' => $fieldType];
            } else if (isset($oldAdditionalData[$fieldName])) {
              $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
            }
          } else {
            if ($request->has("additional_fields.$fieldName")) {
              $structuredFields[$fieldName] = ['label' => $field->label, 'value' => $request->input("additional_fields.$fieldName"), 'type' => $fieldType];
            }
          }
        }
        $investor->additional_data = $structuredFields;
        $investor->save();
      }

      // Close old contracts if any and create new one if data changed
      // For simplicity, we'll just update or create the active one
      $contractData = [
        'commission_type' => $request->commission_type,
        'commission_value' => $request->commission_value,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'filter_customer_ids' => $request->customer_ids,
        'min_commission_threshold' => $request->min_commission_threshold,
        'created_by' => auth()->id(),
        'broker_id' => $request->broker_id ?: null,
        'broker_commission_source' => $request->broker_commission_source ?? 'investor_commission',
        'broker_commission_type' => $request->broker_commission_type ?? 'percentage',
        'broker_commission_value' => $request->broker_commission_value ?? 0.00,
      ];

      // نوع الاستثمار لا يتغير عند التعديل
      if (!$request->id) {
        $contractData['contract_type'] = $request->contract_type;
      }

      InvestmentContract::updateOrCreate(
        ['user_id' => $investor->id, 'status' => 'active'],
        $contractData
      );

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Investor saved successfully')]);
    } catch (Exception $e) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $e->getMessage()]);
    }
  }

  public function show($id)
  {
    $investor = User::with([
      'activeInvestmentContract',
      'investorWallet',
      'userWallet',
      'investmentContracts' => function ($q) {
        $q->orderBy('created_at', 'desc')->limit(5);
      }
    ])->findOrFail($id);

    $fields = Form_Field::where('form_template_id', $investor->form_template_id)->get();
    $investor->fields = $fields;

    return response()->json($investor);
  }

  public function resetPass(Request $request)
  {
    try {
      $user = User::findOrFail($request->id);
      $status = $user->reset_password == 1 ? 0 : 1;
      $user->update(['reset_password' => $status]);

      return response()->json(['status' => 1, 'success' => $status]);
    } catch (Exception $e) {
      return response()->json(['status' => 2, 'error' => $e->getMessage()]);
    }
  }

  public function destroy(Request $request)
  {
    try {
      $investor = User::findOrFail($request->id);
      // Mark as deleted instead of actual delete to preserve financial records
      $investor->update(['status' => 'deleted']);
      return response()->json(['status' => 1, 'success' => __('Investor deleted successfully')]);
    } catch (Exception $e) {
      return response()->json(['status' => 2, 'error' => $e->getMessage()]);
    }
  }

  public function getAvailableHistoricalTasks($id): JsonResponse
  {
    try {
      $investor = User::findOrFail($id);
      $contract = $investor->activeInvestmentContract;

      if (!$contract) {
        return response()->json(['status' => 0, 'error' => __('No active contract for investor')]);
      }

      // جلب المهام التي لم يتم ربطها بمضارب وتم إنشاؤها منذ تاريخ بداية المضاربة
      $tasks = Task::whereNull('investor_id')
        ->where('created_at', '>=', $contract->start_date->startOfDay())
        ->where('payment_status', 'paid') // نفترض أنها مدفوعة مسبقاً كما ذكر المستخدم
        ->with(['customer', 'driver', 'vehicle_size.type.vehicle', 'pickup', 'delivery'])
        ->orderBy('created_at', 'desc')
        ->get();

      return response()->json(['status' => 1, 'tasks' => $tasks]);
    } catch (Exception $e) {
      return response()->json(['status' => 0, 'error' => $e->getMessage()]);
    }
  }

  public function linkHistoricalTasks(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'investor_id' => 'required|exists:users,id',
      'task_ids' => 'required|array',
      'task_ids.*' => 'exists:tasks,id',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => __('Invalid data')]);
    }

    DB::beginTransaction();
    try {
      $investor = User::findOrFail($request->investor_id);
      $wallet = $investor->investorWallet;
      $contract = $investor->activeInvestmentContract;

      if (!$wallet || !$contract) {
        throw new Exception(__('Investor has no wallet or active contract'));
      }

      $tasks = Task::whereIn('id', $request->task_ids)->get();
      $totalInvested = 0;

      foreach ($tasks as $task) {
        if ($task->investor_id)
          continue;

        $taskPrice = (float) $task->total_price;

        // 1. ربط المهمة
        $task->update([
          'investor_id' => $investor->id,
          'investor_payment_status' => 'paid',
        ]);

        // 2. تسجيل عملية المضاربة في محفظة المضاربة (خصم)
        $currentBalance = $wallet->balance - $totalInvested;
        InvestorWalletTransaction::create([
          'investor_wallet_id' => $wallet->id,
          'task_id' => $task->id,
          'transaction_type' => 'debit',
          'amount' => $taskPrice,
          'description' => __('Pay Task Value') . " #{$task->id}",
          'performed_by' => auth()->id(),
          'balance_after' => $currentBalance - $taskPrice,
          'source_type' => 'capital',
        ]);

        $totalInvested += $taskPrice;

        // 3. إذا كان مضارب مهام، نحتسب عمولته فوراً
        if ($contract->contract_type === 'task_investment') {
          $platformCommission = (float) ($task->ad->service_commission ?? $task->commission ?? 0);
          if ($platformCommission > 0) {
            $investorCommission = $contract->calculateCommission($platformCommission);
            if ($investorCommission > 0) {
              $personalWallet = $investor->userWallet;
              if ($personalWallet) {
                UserWalletTransaction::create([
                  'user_wallet_id' => $personalWallet->id,
                  'task_id' => $task->id,
                  'transaction_type' => 'credit',
                  'amount' => $investorCommission,
                  'description' => __('Task commission #:id', ['id' => $task->id]),
                  'status' => true,
                ]);
              }
            }
          }
        }
      }

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Tasks linked successfully')]);
    } catch (Exception $e) {
      DB::rollBack();
      return response()->json(['status' => 0, 'error' => $e->getMessage()]);
    }
  }
}
