<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Settings;
use App\Helpers\IpHelper;
use App\Models\Form_Field;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Customs_Clearance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Clearance_Transactions;
use App\Models\Customs_Clearance_Offer;
use App\Models\Customs_Clearance_History;
use Illuminate\Support\Facades\Validator;
use App\Models\Clearance_Pricing_Template;
use App\Models\Wallet_Transaction;

class CustomsClearanceController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:view_customs_clearances', ['only' => ['index', 'data', 'edit', 'historyData', 'showOffers', 'getOffers']]);
    $this->middleware('permission:create_customs_clearances', ['only' => ['store']]);
    $this->middleware('permission:delete_customs_clearances', ['only' => ['destroy']]);
    $this->middleware('permission:close_customs_clearances', ['only' => ['close']]);
    $this->middleware('permission:assign_customs_clearances', ['only' => ['getToAssign', 'assign']]);
    $this->middleware('permission:status_customs_clearances', ['only' => ['chang_status', 'createAd']]);
    $this->middleware('permission:payment_customs_clearances', ['only' => ['confirmPayment', 'paymentInfo', 'cancelPayment']]);
  }

  public function index()
  {
    $templates = Form_Template::all();
    $customers = Customer::where('status', 'active')->get();
    $customs_clearance = Settings::where('key', 'customs_clearance_template')->first();
    return view('admin.customs-clearances.index', compact('templates', 'customers', 'customs_clearance'));
  }

  public function show($id)
  {
    $data = Customs_Clearance::findOrFail($id);
    return view('admin.customs-clearances.show', compact('data'));
  }

  public function data(Request $request)
  {
    $query = Customs_Clearance::with(['customer', 'user', 'formTemplate', 'clearanceAgent', 'offers']);

    // Filter by form search
    if ($request->filled('search')) {
      $query->Orwhere('id', 'like', "%{$request->search}%")
        ->Orwhere('total_price', 'like', "%{$request->search}%");
    }

    // Filter by status
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    // Filter by closed
    if ($request->filled('closed')) {
      $query->where('closed', $request->closed == 'true' ? 1 : 0);
    }

    if ($request->filled('payment')) {
      $query->where('payment_status', $request->payment);
    }



    // Filter by owner type
    if ($request->filled('owner_type')) {
      if ($request->owner_type === 'customer') {
        $query->whereNotNull('customer_id');
      } elseif ($request->owner_type === 'admin') {
        $query->whereNotNull('user_id');
      }
    }

    // Filter by date range
    if ($request->filled('date_from') && $request->filled('date_to')) {
      $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
    }

    $totalData = $query->count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);

    // Define sortable columns
    $columns = ['id', 'id', 'owner', 'clearance_agent', 'status', 'total_price', 'created_at'];
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'desc');

    // Handle search
    if ($request->filled('search.value')) {
      $search = $request->input('search.value');
      $query->where(function ($q) use ($search) {
        $q->where('id', 'like', "%{$search}%")
          ->orWhere('status', 'like', "%{$search}%")
          ->orWhere('total_price', 'like', "%{$search}%")
          ->orWhereHas('customer', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
          })
          ->orWhereHas('user', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
          })
          ->orWhereHas('clearanceAgent', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
          });
      });
      $totalFiltered = $query->count();
    }

    $clearances = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];

    foreach ($clearances as $clearance) {
      // Determine owner info
      $owner = $clearance->customer ? $clearance->customer : $clearance->user;
      $ownerType = $clearance->customer ? 'Customer' : 'Admin';

      // Status badge
      $statusBadge = $this->getStatusBadge($clearance->status);


      $totalPrice = $clearance->total_price ?? 0;
      $pricing = $clearance->pricing;

      if ($totalPrice > 0 && !$clearance->included) {


        $vat = floatval($pricing->vat_commission); // تحويل إلى رقم والتعامل مع null
        $commission = floatval($pricing->service_commission);
        $commissionType = $pricing->service_commission_type; // 'fixed' or 'percentage'

        $commissionAmount = 0;
        $vatAmount = 0;

        // حساب العمولة فقط إذا كانت قيمة صالحة
        if ($commission > 0) {
          if ($commissionType === 'percentage') {
            $commissionAmount = ($commission / 100) * $totalPrice;
          } else { // fixed
            $commissionAmount = $commission;
          }
        }

        // حساب الضريبة فقط إذا كانت قيمة صالحة
        $priceWithCommission = $totalPrice + $commissionAmount;
        if ($vat > 0) {
          $vatAmount = ($vat / 100) * $priceWithCommission;
        }

        // المجموع النهائي
        $totalPrice += $commissionAmount + $vatAmount;
      }


      // Price info
      $priceInfo = $totalPrice > 0 ?
        '<span class="border border-primary rounded text-primary px-2"><b>' . number_format($totalPrice,  $pricing->decimal_places ?? 2) . ' SAR </b></span>' :
        `<span class="text-muted">__('Not specified')</span>`;

      // Offers count
      $offersCount = $clearance->offers->count();

      // Actions based on status and permissions
      $actions = $this->generateActions($clearance);

      $data[] = [
        'id' => $clearance->id,
        'owner' => [
          'name' => $owner ? $owner->name : __('Not specified'),
          'type' => $ownerType,
          'badge' => $ownerType === 'Customer' ?
            '<span class="">(customer)</span>' :
            '<span class="">(administrator)</span>'
        ],
        'template' => $clearance->formTemplate ? $clearance->formTemplate->name : __('Not specified'),
        'clearance_agent' => $clearance->clearanceAgent ? $clearance->clearanceAgent->name  : __('Not specified'),
        'agent_phone' =>  $clearance->clearanceAgent ?  $clearance->clearanceAgent->phone : '',
        'agent_email' =>  $clearance->clearanceAgent ?  $clearance->clearanceAgent->email : '',
        'status' => $statusBadge,
        'closed' => $clearance->closed,
        'public' => $clearance->public,
        'payment' => $clearance->payment_status,
        'price_info' => $priceInfo,
        'included' => $clearance->included,
        'offers_count' => $offersCount > 0 ?
          '<span class="badge bg-info">' . $offersCount . '</span>' :
          '<span class="text-muted">0</span>',
        'created_at' => $clearance->created_at->format('Y-m-d H:i'),
        'actions' => $actions,
        'can_edit' => $clearance->status === 'in_progress' && !$clearance->closed,
        'can_delete' => $clearance->status === 'in_progress' && !$clearance->closed,
        'can_assign' => $clearance->status === 'in_progress' && !$clearance->clearance_agent_id,
        'can_create_ad' => $clearance->status === 'in_progress' && !$clearance->clearance_agent_id,
        'can_close' => in_array($clearance->status, ['completed', 'in_progress']) && !$clearance->closed,
      ];
    }

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => intval($totalData),
      'recordsFiltered' => intval($totalFiltered),
      'data' => $data,
      'summary' => $this->getStatistics()
    ]);
  }

  private function getStatusBadge($status)
  {
    $badges = [
      'in_progress' => `<span class="badge bg-warning">__('in_progress')</span>`,
      'assign' => `<span class="badge bg-info">__('assign')</span>`,
      'accepted' => `<span class="badge bg-primary">__('accepted')</span>`,
      'start' => `<span class="badge bg-secondary">__('start')</span>`,
      'completed' => `<span class="badge bg-success">__('completed')</span>`,
      'canceled' => `<span class="badge bg-danger">__('canceled')</span>`,
    ];

    return $badges[$status] ?? '<span class="badge bg-light text-dark">' . $status . '</span>';
  }

  private function generateActions($clearance)
  {
    $actions = '<div class="d-flex align-items-center gap-1">';

    // View action
    $actions .= '<a href="' . route('admin.customs-clearances.show', $clearance->id) . '"
                   class="btn btn-sm btn-icon  rounded-pill waves-effect"
                   data-bs-toggle="tooltip" title="View details">
                   <i class="ti ti-eye ti-md"></i>
                 </a>';

    // Edit action
    $actions .= '<button class="btn btn-sm btn-icon  rounded-pill waves-effect edit-record"
                     data-id="' . $clearance->id . '"
                     data-bs-toggle="tooltip" title="edit">
                     <i class="ti ti-edit ti-md"></i>
                   </button>';



    $actions .= '<div class="dropdown">
    <button class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
      <i class="ti ti-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">';


    // Payment
    $actions .= '<li><a href="' . route('customs-clearances.offers',  $clearance->id) . '" class="dropdown-item payment-record"
                    target="_blank" >
                    <i class="ti ti-tags   me-1"></i> Show Offers
                </a></li>';
    // Assign action
    $actions .= '<li><a href="javascript:;" class="dropdown-item assign-clearance"
                    data-id="' . $clearance->id . '">
                    <i class="ti ti-user-plus me-1"></i> Assign Broker
                </a></li>';


    // Change Visibilty
    $actions .= '<li><a href="javascript:;" class="dropdown-item create-ad"
                    data-id="' . $clearance->id . '" data-public="' . $clearance->public . '">
                    <i class="ti ti-speakerphone me-1"></i> Change Visibility
                </a></li>';

    // Change Status
    $actions .= '<li><a href="javascript:;" class="dropdown-item status-record"
                    data-id="' . $clearance->id . '" data-status="' . $clearance->status . '">
                    <i class="ti ti-badge   me-1"></i> Change Status
                </a></li>';

    // Payment
    $actions .= '<li><a href="javascript:;" class="dropdown-item payment-record"
                    data-id="' . $clearance->id . '" >
                    <i class="ti ti-credit-card   me-1"></i> Payment Clearance
                </a></li>';


    // Close action
    $actions .= '<li><a href="javascript:;" class="dropdown-item close-clearance"
                    data-id="' . $clearance->id . '">
                    <i class="ti ti-lock me-1"></i> Close
                </a></li>';


    // Delete action
    $actions .= '<li><a href="javascript:;" class="dropdown-item text-danger delete-record"
                    data-id="' . $clearance->id . '">
                    <i class="ti ti-trash me-1"></i> Delete
                </a></li>';




    $actions .= '</ul></div>';



    return $actions;
  }

  private function getStatistics()
  {
    return [
      'total' => Customs_Clearance::count(),
      'in_progress' => Customs_Clearance::where('status', 'in_progress')->count(),
      'completed' => Customs_Clearance::where('status', 'completed')->count(),
      'canceled' => Customs_Clearance::where('status', 'canceled')->count(),
      'total_value' => Customs_Clearance::sum('total_price'),
    ];
  }

  public function historyData(Request $req)
  {
    $history = Customs_Clearance_History::where('customs_clearance_id', $req->id)->get();
    return response()->json([
      'data' => $history,
      'count' => $history->count(),
    ]);
  }

  public function store(Request $req)
  {
    $rules = [
      'template' => 'required|exists:form_templates,id',
      'owner_type' => 'required|in:admin,customer',
      'customer_id' => 'required_if:owner_type,customer|nullable|exists:customers,id',
      'price'    => 'nullable|numeric|min:0',
      'is_public' => 'nullable|boolean',
      'included' => 'nullable|boolean',
      'notes'    => 'nullable|string'
    ];

    if ($req->filled('template')) {
      $fields = Form_Field::where('form_template_id', $req->template)->get();

      foreach ($fields as $field) {
        $fieldKey = 'additional_fields.' . $field->name;
        $rules[$fieldKey] = [];

        // لا نضع required للحقول المركبة هنا
        if (!$req->filled('id') && $field->required && !in_array($field->type, ['file_expiration_date', 'file_with_text'])) {
          $rules[$fieldKey][] = 'required';
        }

        // إضافة قواعد بناءً على نوع الحقل
        switch ($field->type) {
          case 'text':
            $rules[$fieldKey][] = 'string';
            break;

          case 'number':
            $rules[$fieldKey][] = 'numeric';
            break;
          case 'url':
            $rules[$fieldKey][] = 'url';
            break;
          case 'date':
            $rules[$fieldKey][] = 'date';
            break;

          case 'file':
            $rules[$fieldKey][] = 'file';
            $rules[$fieldKey][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif'; // أنواع موثوقة
            $rules[$fieldKey][] = 'max:10240'; // 10MB
            break;

          case 'image':
            $rules[$fieldKey][] = 'image';
            $rules[$fieldKey][] = 'mimes:jpeg,png,jpg,webp,gif';
            $rules[$fieldKey][] = 'max:5120'; // 5MB
            break;

          case 'file_expiration_date':
            // إزالة القاعدة العامة للحقل الأساسي
            unset($rules[$fieldKey]);

            // قواعد الملف
            $rules[$fieldKey . '_file'] = [];
            $rules[$fieldKey . '_file'][] = 'file';
            $rules[$fieldKey . '_file'][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
            $rules[$fieldKey . '_file'][] = 'max:10240';

            // قواعد تاريخ الانتهاء
            $rules[$fieldKey . '_expiration'] = [];
            $rules[$fieldKey . '_expiration'][] = 'nullable';
            $rules[$fieldKey . '_expiration'][] = 'date';
            $rules[$fieldKey . '_expiration'][] = 'after_or_equal:today';

            // إذا الحقل مطلوب
            if ($field->required) {
              if (!$req->filled('id')) {
                // عند الإنشاء: الملف مطلوب
                $rules[$fieldKey . '_file'][] = 'required';
                $rules[$fieldKey . '_expiration'][] = 'required';
              } else {
                // عند التحديث: إذا تم رفع ملف جديد، تاريخ الانتهاء مطلوب
                if ($req->hasFile("additional_fields.{$field->name}_file")) {
                  $rules[$fieldKey . '_expiration'][] = 'required';
                }
              }
            }

            // قاعدة مهمة: إذا تم رفع ملف، التاريخ مطلوب (حتى لو الحقل غير مطلوب)
            if ($req->hasFile("additional_fields.{$field->name}_file")) {
              $rules[$fieldKey . '_expiration'][] = 'required';
            }

            break;

          case 'file_with_text':
            // إزالة القاعدة العامة للحقل الأساسي
            unset($rules[$fieldKey]);

            // قواعد الملف
            $rules[$fieldKey . '_file'] = [];
            $rules[$fieldKey . '_file'][] = 'file';
            $rules[$fieldKey . '_file'][] = 'mimes:pdf,doc,docx,xls,xlsx,txt,csv,jpeg,png,jpg,webp,gif';
            $rules[$fieldKey . '_file'][] = 'max:10240';

            // قواعد النص/الرقم
            $rules[$fieldKey . '_text'] = [];
            $rules[$fieldKey . '_text'][] = 'nullable';
            $rules[$fieldKey . '_text'][] = 'string';
            $rules[$fieldKey . '_text'][] = 'max:255';

            // إذا الحقل مطلوب
            if ($field->required) {
              if (!$req->filled('id')) {
                // عند الإنشاء: الملف مطلوب
                $rules[$fieldKey . '_file'][] = 'required';
                $rules[$fieldKey . '_text'][] = 'required';
              } else {
                // عند التحديث: إذا تم رفع ملف جديد، النص مطلوب
                if ($req->hasFile("additional_fields.{$field->name}_file")) {
                  $rules[$fieldKey . '_text'][] = 'required';
                }
              }
            }

            // قاعدة مهمة: إذا تم رفع ملف، النص مطلوب (حتى لو الحقل غير مطلوب)
            if ($req->hasFile("additional_fields.{$field->name}_file")) {
              $rules[$fieldKey . '_text'][] = 'required';
            }

            break;

          default:
            if (!$field->required) {
              $rules[$fieldKey][] = 'nullable';
            }
            $rules[$fieldKey][] = 'string';
            break;
        }
      }
    }
    // إنشاء رسائل خطأ مخصصة لحقول file_expiration_date
    $customMessages = [];
    if ($req->filled('template')) {
      $template = Form_Template::with('fields')->find($req->template);
      foreach ($template->fields as $field) {
        if ($field->type === 'file_expiration_date') {
          $fieldKey = 'additional_fields.' . $field->name;
          $customMessages = array_merge($customMessages, [
            $fieldKey . '_file.required' => __('The :attribute file is required.', ['attribute' => $field->label]),
            $fieldKey . '_file.file' => __('The :attribute must be a valid file.', ['attribute' => $field->label]),
            $fieldKey . '_file.mimes' => __('The :attribute must be a file of type: pdf, doc, docx, xls, xlsx, txt, csv, jpeg, png, jpg, webp, gif.', ['attribute' => $field->label]),
            $fieldKey . '_file.max' => __('The :attribute file size must not exceed 10MB.', ['attribute' => $field->label]),
            $fieldKey . '_expiration.required' => __('The expiration date for :attribute is required.', ['attribute' => $field->label]),
            $fieldKey . '_expiration.date' => __('The expiration date for :attribute must be a valid date.', ['attribute' => $field->label]),
            $fieldKey . '_expiration.after_or_equal' => __('The expiration date for :attribute must be today or a future date.', ['attribute' => $field->label]),
          ]);
        }

        if ($field->type === 'file_with_text') {
          $fieldKey = 'additional_fields.' . $field->name;
          $customMessages = array_merge($customMessages, [
            $fieldKey . '_file.required' => __('The :attribute file is required.', ['attribute' => $field->label]),
            $fieldKey . '_file.file' => __('The :attribute must be a valid file.', ['attribute' => $field->label]),
            $fieldKey . '_file.mimes' => __('The :attribute must be a file of type: pdf, doc, docx, xls, xlsx, txt, csv, jpeg, png, jpg, webp, gif.', ['attribute' => $field->label]),
            $fieldKey . '_file.max' => __('The :attribute file size must not exceed 10MB.', ['attribute' => $field->label]),
            $fieldKey . '_text.required' => __('The text field for :attribute is required.', ['attribute' => $field->label]),
            $fieldKey . '_text.string' => __('The text field for :attribute must be a valid text.', ['attribute' => $field->label]),
            $fieldKey . '_text.max' => __('The text field for :attribute must not exceed 255 characters.', ['attribute' => $field->label]),
          ]);
        }
      }
    }
    $validator = Validator::make($req->all(), $rules, $customMessages);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    DB::beginTransaction();
    $filesToDelete = []; // ❗ قائمة بالملفات التي ستحذف بعد نجاح المعاملة

    try {

      // Determine owner based on owner_type
      $data = [
        'form_template_id' => $req->template,
        'customer_id' => $req->owner_type === 'customer' ? $req->customer_id : null,
        'user_id' => $req->owner_type === 'admin' ? Auth::id() : null,
        'total_price'  => $req->price ?? 0,
        'included'  => $req->included ?? 0,
        'public'  =>   $req->is_public ?? false,
        'notes'  => $req->notes

      ];
      $data['additional_data'] = [];

      $structuredFields = [];
      $oldAdditionalData = [];

      if ($req->filled('id')) {
        $existing = Customs_Clearance::find($req->id);
        if ($existing) {
          $oldAdditionalData = $existing->additional_data ?? [];

          // حذف ملفات النموذج السابق إن تغيّر النموذج
          if ($existing->form_template_id && $existing->form_template_id != $req->template) {
            foreach ($oldAdditionalData as $field) {
              if (in_array($field['type'], ['file', 'image'])) {
                $filesToDelete[] = $field['value']; // حذف لاحق بعد commit
              }
            }
          }
        }
      }

      if ($req->filled('template')) {
        $template = Form_Template::with('fields')->find($req->input('template'));

        foreach ($template->fields as $field) {
          $fieldName = $field->name;
          $fieldType = $field->type;

          if ($field->type === 'file_expiration_date') {
            $fileFieldName = $fieldName . '_file';
            $expirationFieldName = $fieldName . '_expiration';

            // معالجة الملف
            if ($req->hasFile("additional_fields.$fileFieldName")) {
              // حذف الملف القديم إذا موجود
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              }
              $path = FileHelper::uploadFile($req->file("additional_fields.$fileFieldName"), 'customers/files');

              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $path,
                'expiration' => $req->input("additional_fields.$expirationFieldName"),
                'type'  => $field->type,
              ];
            } else {
              // في حال لم يتم رفع ملف جديد، نحتفظ بالبيانات القديمة مع تحديث تاريخ الانتهاء إذا تم تغييره
              if (isset($oldAdditionalData[$fieldName])) {
                $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
                if ($req->filled("additional_fields.$expirationFieldName")) {
                  $structuredFields[$fieldName]['expiration'] = $req->input("additional_fields.$expirationFieldName");
                }
              }
            }
          } else if ($field->type === 'file_with_text') {
            $fileFieldName = $fieldName . '_file';
            $textFieldName = $fieldName . '_text';

            // معالجة الملف
            if ($req->hasFile("additional_fields.$fileFieldName")) {
              // حذف الملف القديم إذا موجود
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value'];
              }
              $path = FileHelper::uploadFile($req->file("additional_fields.$fileFieldName"), 'customers/files');

              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $path,
                'text' => $req->input("additional_fields.$textFieldName"),
                'type'  => $field->type,
              ];
            } else {
              // في حال لم يتم رفع ملف جديد، نحتفظ بالبيانات القديمة مع تحديث النص إذا تم تغييره
              if (isset($oldAdditionalData[$fieldName])) {
                $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
                if ($req->filled("additional_fields.$textFieldName")) {
                  $structuredFields[$fieldName]['text'] = $req->input("additional_fields.$textFieldName");
                }
              }
            }
          } else if (in_array($fieldType, ['file', 'image'])) {
            if ($req->hasFile("additional_fields.$fieldName")) {
              if (isset($oldAdditionalData[$fieldName]['value'])) {
                $filesToDelete[] = $oldAdditionalData[$fieldName]['value']; // حذف لاحقًا
              }

              $path = FileHelper::uploadFile($req->file("additional_fields.$fieldName"), 'customers/files');

              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $path,
                'type'  => $fieldType,
              ];
            } elseif (isset($oldAdditionalData[$fieldName])) {
              $structuredFields[$fieldName] = $oldAdditionalData[$fieldName];
            }
          } else {
            if ($req->has("additional_fields.$fieldName")) {
              $structuredFields[$fieldName] = [
                'label' => $field->label,
                'value' => $req->input("additional_fields.$fieldName"),
                'type'  => $fieldType,
              ];
            }
          }
        }

        $data['additional_data'] = $structuredFields;
      }

      $pricing = Clearance_Pricing_Template::availableForCustomer($req->template, $req->customer_id)->first();
      if (!$pricing) {
        return response()->json(['status' => 2, 'error' => __('There is no Pricing Role match with your selections')]);
      }

      $data['pricing_id'] = $pricing->id;


      if ($req->filled('id')) {
        $find = Customs_Clearance::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Customs Clearance')]);
        }
        if ($find->closed || $find->clearance_agent_id != null || $find->payment_status != 'waiting') {
          return response()->json(['status' => 2, 'error' => __('Can not Edit this Customs Clearance in its current status')]);
        }
        $user = auth()->user();
        if (!$user || !$user->checkCustomer($find->id)) {
          return response()->json(['status' => 2,  'error' => __('You do not have permission to do actions to this record')]);
        }

        $done = $find->update($data);

        // Create history record for update
        if ($done) {
          Customs_Clearance_History::create([
            'customs_clearance_id' => $find->id,
            'action_type' => 'updated',
            'description' => 'Customs clearance information updated',
            'user_id' => auth()->id(),
            'ip' => request()->ip()
          ]);
        }
      } else {
        $done = Customs_Clearance::create($data);

        // Create history record for creation
        if ($done) {
          Customs_Clearance_History::create([
            'customs_clearance_id' => $done->id,
            'action_type' => 'created',
            'description' => 'Customs clearance request created',
            'user_id' => auth()->id(),
            'ip' => request()->ip()
          ]);
        }
      }


      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Customs Clearance')]);
      }

      DB::commit();

      // 🧹 حذف الملفات بعد نجاح التخزين
      foreach ($filesToDelete as $file) {
        FileHelper::deleteFileIfExists($file);
      }

      return response()->json([
        'status'  => 1,
        'success' => __('Customs Clearance saved successfully'),
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function edit($id)
  {
    $data = Customs_Clearance::findOrFail($id);
    $fields = Form_Field::where('form_template_id', $data->form_template_id)->get();
    $data->fields = $fields;

    return response()->json($data);
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();
    try {
      $find = Customs_Clearance::findOrFail($req->id);
      if ($find->status != 'in_progress' || $find->closed == true || $find->clearance_agent_id != 'null' || $find->payment_status != 'waiting') {
        return response()->json(['status' => 2, 'error' => 'You can not delete this Customs Clearance in current status']);
      }
      $done = $find->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Customs Clearance']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Customs Clearance deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  // ============================================= Customs Clearance Offers
  public function showOffers($id)
  {
    $data = Customs_Clearance::findOrFail($id);
    // $offer = Customs_Clearance_Offer::where('customs_clearance_id', $id)->where('driver_id', Auth::user()->id)->first();
    return view('admin.customs-clearances.offers', compact('data'));
  }

  public function getOffers(Request $req)
  {
    $offers = Customs_Clearance_Offer::where('customs_clearance_id', $req->id)->get();

    $transformed = $offers->map(function ($offer) {
      $clearance_status = false;

      if ($offer->customsClearance->clearance_agent_id || $offer->customsClearance->status !== 'in_progress' || $offer->customsClearance->closed === true || $offer->customsClearance->customs_clearance_agent_id != null) {
        $clearance_status = true;
      }

      return [
        'id' => $offer->id,
        'broker' => $offer->broker,
        'clearance_closed' => $clearance_status,
        'broker_id' => $offer->clearance_agent_id,
        'price' => $offer->price,
        'accepted' => $offer->accepted,
        'description' => $offer->description,
      ];
    });

    return response()->json([
      'data' => $transformed,
      'count' => $transformed->count(),
    ]);
  }


  public function acceptOffer($id)
  {
    $offer = Customs_Clearance_Offer::findOrFail($id);
    if (!$offer->customsClearance || $offer->customsClearance->status !== 'in_progress' || $offer->customsClearance->closed == true || $offer->customsClearance->public == false || $offer->customsClearance->clearance_agent_id != null) {
      return response()->json([
        'status' => 2,
        'error' => 'You do not have the right permission to do this action'
      ]);
    }


    if ($offer->accepted) {
      return response()->json(['status' => 2, 'error' => 'This offer is already accepted']);
    }

    Customs_Clearance_Offer::where('customs_clearance_id', $offer->ad_id)->update(['accepted' => false]);

    $offer->accepted = true;
    $offer->save();
    return response()->json(['status' => 1, 'success' => __('The Offer accepted successfully')]);

    return response()->json(['message' => 'Offer accepted successfully.', 'offer' => $offer]);
  }

  public function retractOffer($id)
  {
    $offer = Customs_Clearance_Offer::with('ad.task')->findOrFail($id);
    if (!$offer->customsClearance || $offer->customsClearance->status !== 'in_progress' || $offer->customsClearance->closed == true || $offer->customsClearance->public == false || $offer->customsClearance->clearance_agent_id != null) {
      return response()->json([
        'status' => 2,
        'error' => 'You do not have the right permission to do this action'
      ]);
    }


    if (!$offer->accepted) {
      return response()->json(['status' => 2, 'error' => 'This offer is already Retracted']);
    }

    Customs_Clearance_Offer::where('task_ad_id', $offer->ad_id)->update(['accepted' => true]);

    $offer->accepted = false;
    $offer->save();
    return response()->json(['status' => 1, 'success' => __('The Offer accepted successfully')]);

    return response()->json(['message' => 'Offer accepted successfully.', 'offer' => $offer]);
  }



  // =============================================

  public function storeOffer(Request $req)
  {
    $rules = [
      'clearance' => 'required|exists:customs_clearances,id',
      'clearance_agent' => 'required|exists:customers,id',
      'price' => 'required|numeric',
      'description' => 'nullable|string|max:400',
    ];

    $validator = Validator::make($req->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'error'  => $validator->errors()
      ]);
    }

    DB::beginTransaction();
    try {
      $data = [
        'customs_clearance_id' => $req->clearance,
        'clearance_agent_id' => $req->clearance_agent,
        'price' => $req->price,
        'description' => $req->description,
      ];

      $done = Customs_Clearance_Offer::create($data);

      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Offer')]);
      }

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Offer saved successfully')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function statistics()
  {
    try {
      $stats = $this->getStatistics();

      return response()->json([
        'success' => true,
        'data' => $stats
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to load statistics'
      ], 500);
    }
  }


  public function chang_status(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:customs_clearance,id',
      'status' => 'required|in:in_progress,started,completed,canceled',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'type' => 'error', 'message' => $req->id]);
    }

    try {
      $find = Customs_Clearance::find($req->id);
      $user = auth()->user();
      // if (!$user || !$user->checkTask($req->id)) {
      //   return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
      // }
      if ($find->public) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('This Customs Clearance is in Public mode you can not change status')]);
      }
      if ($find->closed) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => __('This Customs Clearance is already closed')]);
      }
      $data = [
        'status' => $req->status
      ];
      if ($req->status === 'completed') {
        $data['completed_at'] = now();
      }

      $done = $find->update($data);

      $userIp = IpHelper::getUserIpAddress();
      $history = [
        [
          'action_type' => $req->status,
          'description' => 'Change status from ' . $find->status,
          'ip' => $userIp,
          'user_id' => Auth::user()->id
        ]
      ];
      $find->history()->createMany($history);
      if (!$done) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'error to Change Customs Clearance Status']);
      }
      return response()->json(['status' => 1, 'type' => 'success', 'message' => 'Customs Clearance Status changed']);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'type' => 'error', 'message' => $ex->getMessage()]);
    }
  }

  public function getToAssign($id)
  {
    try {
      $data = Customs_Clearance::findOrFail($id);
      if (!in_array($data->status, ['in_progress'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This Clearance cannot be modified in its current state'),
        ]);
      }
      $brokers = Customer::where('is_customs_clearance_agent', 1)->where('status', 'active')->get();
      $totalPrice = $data->total_price ?? 0;
      $pricing = $data->pricing;

      if ($totalPrice > 0 && !$data->included) {
        $vat = floatval($pricing->vat_commission); // تحويل إلى رقم والتعامل مع null
        $commission = floatval($pricing->service_commission);
        $commissionType = $pricing->service_commission_type; // 'fixed' or 'percentage'

        $commissionAmount = 0;
        $vatAmount = 0;

        // حساب العمولة فقط إذا كانت قيمة صالحة
        if ($commission > 0) {
          if ($commissionType === 'percentage') {
            $commissionAmount = ($commission / 100) * $totalPrice;
          } else { // fixed
            $commissionAmount = $commission;
          }
        }

        // حساب الضريبة فقط إذا كانت قيمة صالحة
        $priceWithCommission = $totalPrice + $commissionAmount;
        if ($vat > 0) {
          $vatAmount = ($vat / 100) * $priceWithCommission;
        }

        // المجموع النهائي
        $totalPrice += $commissionAmount + $vatAmount;
      }
      $data->brokers = $brokers;
      $data->total_price =  $totalPrice;
      return response()->json($data);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function assign(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'required|exists:customs_clearance,id',
      'price' => 'required|numeric',
      'commission' => 'required|numeric',
      'broker' => 'required|exists:customers,id',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    DB::beginTransaction();
    try {
      $data = Customs_Clearance::find($req->id);
      $broker = Customer::find($req->broker);
      if ($broker->is_customs_clearance_agent != 1) {
        return response()->json(['status' => 2, 'error' => __('The Selected Broker not found')]);
      }

      if ($data->closed) {
        return response()->json(['status' =>  2, 'type' => 'error', 'message' => 'This Customs Clearance is already closed']);
      }

      if (!in_array($data->status, ['in_progress'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This Customs Clearance cannot be modified in its current state'),
        ]);
      }

      $userIp = IpHelper::getUserIpAddress();
      $history = [
        [
          'action_type' => 'assign',
          'description' => 'assign Customs Clearance manual',
          'ip' => $userIp,
          'user_id' => Auth::user()->id,
          'clearance_agent_id' => $broker->id
        ]
      ];

      $data->clearance_agent_id = $broker->id;
      $data->total_price = $req->price;
      $data->commission_type = 'manual';
      $data->commission = $req->commission;
      $data->status = 'assign';
      $data->public = 0;



      $data->history()->createMany($history);

      $data->save();


      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Customs Clearance assigned successfully')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function createAd($id)
  {
    DB::beginTransaction();

    try {
      $clearance = Customs_Clearance::findOrFail($id);

      if ($clearance->status !== 'in_progress' || $clearance->closed === true || $clearance->clearance_agent_id) {
        return response()->json([
          'success' => false,
          'message' => __('Cannot change visibility for this clearance')
        ]);
      }

      // Toggle visibility
      $clearance->public = !$clearance->public;
      $clearance->save();

      // Add history record
      $statusText = $clearance->public ? 'Public' : 'Private';
      $userIp = IpHelper::getUserIpAddress();

      Customs_Clearance_History::create([
        'customs_clearance_id' => $clearance->id,
        'action_type' => 'change visibility',
        'description' => 'Changed visibility to ' . $statusText,
        'ip' => $userIp,
        'user_id' => Auth::id()
      ]);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Visibility updated successfully'
      ]);
    } catch (\Exception $e) {
      DB::rollback();

      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ]);
    }
  }

  public function close($id)
  {
    try {
      $clearance = Customs_Clearance::findOrFail($id);

      if (!in_array($clearance->status, ['completed'])) {
        return response()->json([
          'success' => false,
          'message' => 'Cannot close this clearance'
        ]);
      }
      if ($clearance->closed) {
        return response()->json([
          'success' => false,
          'message' => 'This clearance is already closed'
        ]);
      }

      if ($clearance->clearance_agent_id === null) {
        return response()->json([
          'success' => false,
          'message' => 'Cannot close this clearance. This clearance is not assigned to any agent'
        ]);
      }
      if ($clearance->payment_status !== 'completed') {
        return response()->json([
          'success' => false,
          'message' => 'Cannot close this clearance. This clearance is not paid yet'
        ]);
      }

      $clearance->update([
        'closed' => true,
        'closed_at' => now()
      ]);

      $ip = IpHelper::getUserIpAddress();
      // Add history record
      Customs_Clearance_History::create([
        'customs_clearance_id' => $clearance->id,
        'action_type' => 'close',
        'description' => 'Clearance closed',
        'ip' => $ip,
        'user_id' => Auth::id()
      ]);

      $wallet =  $clearance->clearanceAgent->wallet;
      if (!$wallet) {
        return response()->json([
          'success' => false,
          'message' => 'Cannot close this clearance. This clearance agent does not have a wallet'
        ]);
      }

      $data = [
        'amount'              => $clearance->total_price - $clearance->commission,
        'description'         => 'Amount for Customs Clearance #' . $clearance->id,
        'transaction_type'    => 'credit',
        'wallet_id'           => $wallet->id,
        'maturity_time'       => Carbon::now()->copy()->addDays(3),
      ];

      Wallet_Transaction::create($data);


      return response()->json([
        'success' => true,
        'message' => 'Clearance closed successfully'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to close clearance'
      ], 500);
    }
  }















  public function paymentInfo($id)
  {
    try {
      $data = Customs_Clearance::findOrFail($id);
      if (in_array($data->status, ['in_progress']) || $data->public) {
        return response()->json([
          'status' => 2,
          'error' => __('This Customs Clearance cannot be Payed in its current state'),
        ]);
      }
      if ($data->payment_status !== 'waiting') {
        $transiction = Clearance_Transactions::with('user')->where('reference_id', $data->id)->first();
        return response()->json([
          'status' => 3,
          'message' => __('This Customs Clearance has already make payment request and it is ' . $data->payment_status),
          'data' => $transiction
        ]);
      }
      return response()->json($data);
    } catch (Exception $e) {
      return response()->json([
        'status' => 2,
        'error' => __('Customs Clearance not found')
      ]);
    }
  }


  public function confirmPayment($id)
  {
    DB::beginTransaction();
    try {
      $data = Customs_Clearance::findOrFail($id);
      $user = auth()->user();
      // if (!$user || !$user->checkTask($data->id)) {
      //   return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
      // }
      if (in_array($data->status, ['in_progress']) || $data->public) {
        return response()->json([
          'status' => 2,
          'error' => __('This Customs Clearance cannot be Payed in its current state'),
        ]);
      }

      if ($data->payment_status === 'pending') {
        $transaction = Clearance_Transactions::where('reference_id', $data->id)->first();
        if (!$transaction) {
          return response()->json([
            'status' => 2,
            'error' => __('Transaction not found')
          ]);
        }
        $transaction->update([
          'status' => 'paid',
          'user_check' => Auth::user()->id,
          'user_ip' => IpHelper::getUserIpAddress(),
          'checkout_at' => Carbon::now(),
        ]);
        $data->update([
          'payment_status' => 'completed'
        ]);

        DB::commit();
        return response()->json([
          'status' => 1,
          'message' => __('Payment has been confirmed for task') . ' #' . $data->id,
        ]);
      }
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('You can not confirm payment for this task'),
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('Task not found')
      ]);
    }
  }

  public function cancelPayment($id)
  {
    DB::beginTransaction();
    try {
      $data = Customs_Clearance::findOrFail($id);
      $user = auth()->user();
      if (!$user || !$user->checkTask($data->id)) {
        return response()->json(['status' => 2, 'type' => 'error', 'message' => __('You do not have permission to do actions to this record')]);
      }
      if (in_array($data->status, ['in_progress'])) {
        return response()->json([
          'status' => 2,
          'error' => __('This task cannot be Payed in its current state'),
        ]);
      }
      if ($data->payment_status === 'pending') {
        $transaction = Clearance_Transactions::where('reference_id', $data->id)->first();
        if (!$transaction) {
          return response()->json([
            'status' => 2,
            'error' => __('Transaction not found')
          ]);
        }

        Clearance_Transactions::where('reference_id', $data->id)->delete();

        $data->update([
          'payment_status' => 'waiting'
        ]);

        if ($transaction->receipt_image) {
          unlink($transaction->receipt_image);
        }
        DB::commit();
        return response()->json([
          'status' => 1,
          'message' => __('Payment has been canceled for Customs Clearance') . ' #' . $data->id,
        ]);
      }
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('You can not cancel payment for this Customs Clearance ' . $data->payment_status),
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'message' => __('Task not found')
      ]);
    }
  }
}
