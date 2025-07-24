<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Customs_Clearance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Customs_Clearance_History;
use App\Models\Form_Field;
use Illuminate\Support\Facades\Validator;

class CustomsClearanceController extends Controller
{
  public function index()
  {
    return view('admin.customs-clearances.index');
  }

  public function show(Request $req)
  {
    $data = Customs_Clearance::findOrFail($req->id);
    return view('admin.customs-clearances.show', compact('data'));
  }

  public function data(Request $request)
  {
    $query = Customs_Clearance::query();

    // Filter by status
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    // Filter by closed
    if ($request->filled('closed')) {
      $query->where('closed', $request->closed);
    }

    // Filter by date range
    if ($request->filled('date_from') && $request->filled('date_to')) {
      $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
    }

    $totalData = $query->count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'asc');

    $clearances = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $fakeId = $start;

    foreach ($clearances as $clearance) {
      $data[] = [
        'id' => $clearance->id,
        'fake_id' => ++$fakeId,
        'owner' => $clearance->owner->name,
        'clearance_agent' => $clearance->clearanceAgent->name ?? '',
        'status' => $clearance->status,
        'closed' => $clearance->closed,
        'price_info' => $clearance->total_price,
        'created_at' => $clearance->created_at->format('Y-m-d H:i'),
      ];
    }

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => intval($totalData),
      'recordsFiltered' => intval($totalFiltered),
      'code' => 200,
      'data' => $data,
    ]);
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
      'customer' => 'nullable|exists:customers,id',
      'user' => 'nullable|exists:users,id',
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
      $data = [
        'form_template_id' => $req->template,
        'customer_id' => $req->customer,
        'user_id' => $req->user,
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

      if ($req->filled('id')) {
        $find = Customs_Clearance::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Customs Clearance')]);
        }
        $user = auth()->user();
        if (!$user || !$user->checkCustomer($find->id)) {
          return response()->json(['status' => 2,  'error' => __('You do not have permission to do actions to this record')]);
        }

        $done = $find->update($data);
      } else {
        $done = Customs_Clearance::create($data);
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

    $data->fields =  $fields;

    return response()->json($data);
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();
    try {
      $find = Customs_Clearance::findOrFail($req->id);
      if ($find->status !== 'in_progress' || $find->closed === true) {
        return response()->json(['status' => 2, 'error' => 'You can not delete this Customs Clearance']);
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


  public function offersData(Request $req)
  {
    $clearance = Customs_Clearance::findOrFail($req->id);
    $offers = $clearance->offers()->with('clearanceAgent')->get();
    return response()->json([
      'data' => $offers,
      'count' => $offers->count(),
    ]);
  }

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
}
