<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Pricing;
use App\Models\Vehicle;
use App\Models\Form_Field;
use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Models\Pricing_Method;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Geofence;
use App\Models\Tag;
use App\Models\Pricing_Template;
use App\Models\Pricing_Field;
use App\Models\Pricing_Geofence;
use App\Models\Pricing_Parametar;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\DumpCompletionCommand;

class TemplateController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:templates_settings', ['only' => ['index', 'getData', 'edit',  'store', 'update']]);
  }

  public function index()
  {
    return view('admin.settings.templates.index');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'description',
      4 => 'usage', // عمود الاستخدامات
      5 => 'created_at',
    ];

    $search = [];

    $totalData = Form_Template::count();
    $totalFiltered = $totalData;

    $limit = $request->input('length', 10);
    $start = $request->input('start', 0);
    $orderColumnIndex = $request->input('order.0.column', 1);
    $order = $columns[$orderColumnIndex] ?? 'id';
    $dir = $request->input('order.0.dir', 'desc');

    // تجهيز الاستعلام الرئيسي
    $query = Form_Template::query();

    if (!empty($request->input('search.value'))) {
      $search = $request->input('search.value');
      $query->where('id', 'LIKE', "%{$search}%")
        ->orWhere('name', 'LIKE', "%{$search}%")
        ->orWhere('description', 'LIKE', "%{$search}%");
    }

    $totalFiltered = $query->count();

    $methods = $query->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->withCount(['tasks', 'customers', 'drivers', 'users', 'customsClearances'])
      ->get();


    $data = [];
    $fakeId = $start;


    foreach ($methods as $method) {
      // حساب إجمالي الاستخدامات
      $totalUsage = $method->tasks_count + $method->customers_count + $method->drivers_count + $method->users_count;

      // تكوين نص الاستخدامات
      $usageDetails = [];
      if ($method->tasks_count > 0) {
        $usageDetails[] = __('Tasks') . ': ' . $method->tasks_count;
      }
      if ($method->customers_count > 0) {
        $usageDetails[] = __('Customers') . ': ' . $method->customers_count;
      }
      if ($method->drivers_count > 0) {
        $usageDetails[] = __('Drivers') . ': ' . $method->drivers_count;
      }
      if ($method->users_count > 0) {
        $usageDetails[] = __('Users') . ': ' . $method->users_count;
      }
      if ($method->customsClearances->count() > 0) {
        $usageDetails[] = __('Clearance') . ': ' . $method->customsClearances->count();
      }

      $usageSummary = empty($usageDetails) ? __('No usage') : implode('<br>', $usageDetails);

      $data[] = [
        'id' => $method->id,
        'fake_id' => ++$fakeId,
        'name' => $method->name,
        'description' => $method->description ?? '',
        'usage_summary' => $usageSummary,
        'total_usage' => $totalUsage,
        'created_at' => $method->created_at->format('Y-m-d H:i'),
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

  public function getFields(Request $req)
  {
    $data = Form_Field::where('form_template_id', $req->id)->orderBy('order', 'ASC')->get();
    if ($data->count() <= 0) {
      return response()->json(['status' => 2, 'error' => __('no fields in this template')]);
    }
    return response()->json(['status' => 1, 'fields' => $data, 'success' => __('Template Created')]);
  }

  public function getPricing(Request $req)
  {
    $fields = Form_Field::where('form_template_id', $req->id)->get();

    $data = Pricing::where('pricing_template_id', $req->id)->where('status', 1)->get();
    if ($fields->count() <= 0) {
      return response()->json(['status' => 2, 'error' => __('no fields in this template')]);
    }
    return response()->json(['status' => 1, 'fields' => $fields, 'pricing' => $data, 'success' => __('Template Created')]);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:form_templates,name,' .  ($req->id ?? 0),
      'description' => 'nullable|string',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    try {
      $data = [
        'name'          => $req->name,
        'description'   => $req->description,
      ];
      if ($req->filled('id')) {
        $find = Form_Template::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Template')]);
        }
        $done = $find->update($data);
        $message =  __('Template Updated successfully');
      } else {
        $done = Form_Template::create($data);
        $message =  __('Template Created successfully');
      }

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Template')]);
      }
      return response()->json(['status' => 1, 'success' => $message]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function edit($id)
  {
    $data = Form_Template::with('fields', 'pricing_templates')->find($id);
    $vehicle = Vehicle::all();
    $methods = Pricing_Method::where('status', 1)->get();
    $customers = Customer::all();
    $tags = Tag::whereHas('customers')->get();
    $pricing_methods = Pricing_Method::where('status', 1)->get();
    $geofences = Geofence::all();
    if (!$data) {
      return redirect()->back();
    }
    return view('admin.settings.templates.edit', compact('data', 'vehicle', 'methods', 'tags', 'customers', 'pricing_methods', 'geofences'));
  }

  /**
   * Duplicate a Form Template with all its related configuration data
   *
   * @param int $id The ID of the template to duplicate
   * @return \Illuminate\Http\JsonResponse
   */
  public function duplicate($id)
  {
    try {
      DB::beginTransaction();

      // Find the original template with all its relationships
      $originalTemplate = Form_Template::with([
        'fields',
        'pricing_templates.pricing_methods.parametars',
        'pricing_templates.geoFences',
        'pricing_templates.tags',
        'pricing_templates.sizes',
        'pricing_templates.customers'
      ])->find($id);

      if (!$originalTemplate) {
        return response()->json([
          'status' => 2,
          'error' => __('Template not found')
        ]);
      }

      // Create the new template with a unique name
      $newTemplateName = $this->generateUniqueName($originalTemplate->name);
      $newTemplate = Form_Template::create([
        'name' => $newTemplateName,
        'description' => $originalTemplate->description
      ]);

      // Duplicate Form Fields
      $this->duplicateFormFields($originalTemplate, $newTemplate);

      // Duplicate Pricing Templates and their related data
      $this->duplicatePricingTemplates($originalTemplate, $newTemplate);

      DB::commit();

      return response()->json([
        'status' => 1,
        'success' => __('Template duplicated successfully'),
        'data' => [
          'id' => $newTemplate->id,
          'name' => $newTemplate->name
        ]
      ]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json([
        'status' => 2,
        'error' => $ex->getMessage()
      ]);
    }
  }

  /**
   * Generate a unique name for the duplicated template
   *
   * @param string $originalName
   * @return string
   */
  private function generateUniqueName($originalName)
  {
    $baseName = $originalName . ' - Copy';
    $counter = 1;
    $newName = $baseName;

    while (Form_Template::where('name', $newName)->exists()) {
      $newName = $baseName . ' (' . $counter . ')';
      $counter++;
    }

    return $newName;
  }



  /**
   * Duplicate Form Fields for the new template
   *
   * @param Form_Template $originalTemplate
   * @param Form_Template $newTemplate
   */
  private function duplicateFormFields($originalTemplate, $newTemplate)
  {
    foreach ($originalTemplate->fields as $field) {
      Form_Field::create([
        'form_template_id' => $newTemplate->id,
        'name' => $field->name,
        'label' => $field->label,
        'type' => $field->type,
        'required' => $field->required,
        'value' => $field->value,
        'driver_can' => $field->driver_can,
        'customer_can' => $field->customer_can,
        'ocr_prompt' => $field->ocr_prompt,
        'is_auto_filled' => $field->is_auto_filled
      ]);
    }
  }

  /**
   * Duplicate Pricing Templates and all their related data
   *
   * @param Form_Template $originalTemplate
   * @param Form_Template $newTemplate
   */
  private function duplicatePricingTemplates($originalTemplate, $newTemplate)
  {
    foreach ($originalTemplate->pricing_templates as $pricingTemplate) {
      // Create new pricing template with same name (no need to add "Copy")
      $newPricingTemplate = Pricing_Template::create([
        'name' => $pricingTemplate->name,
        'decimal_places' => $pricingTemplate->decimal_places,
        'base_fare' => $pricingTemplate->base_fare,
        'base_waiting_time' => $pricingTemplate->base_waiting_time,
        'waiting_fare' => $pricingTemplate->waiting_fare,
        'base_distance' => $pricingTemplate->base_distance,
        'distance_fare' => $pricingTemplate->distance_fare,
        'discount_percentage' => $pricingTemplate->discount_percentage,
        'vat_commission' => $pricingTemplate->vat_commission,
        'service_tax_commission' => $pricingTemplate->service_tax_commission,
        'all_customer' => $pricingTemplate->all_customer,
        'form_template_id' => $newTemplate->id
      ]);

      // Duplicate pricing methods and their parameters
      $this->duplicatePricingMethods($pricingTemplate, $newPricingTemplate);

      // Duplicate geofence pricing
      $this->duplicateGeofencePricing($pricingTemplate, $newPricingTemplate);

      // Duplicate many-to-many relationships (configuration data only)
      $this->duplicatePricingRelationships($pricingTemplate, $newPricingTemplate);
    }
  }

  public function update(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id' => 'required|exists:form_templates,id',
      'fields' => 'required|array|min:1',
      'fields.*.name' => 'required|string',
      'fields.*.label' => 'required|string',
      'fields.*.type' => 'required|in:string,number,email,date,select,file,image,file_expiration_date,file_with_text,url',
      'fields.*.required' => 'required|boolean',
      'fields.*.value' => 'nullable|string',
      'fields.*.driver_can' => 'required|in:hidden,read,write',
      'fields.*.customer_can' => 'required|in:hidden,read,write',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'message' => 'Validator Error', 'error' => $validator->errors()->toArray()]);
    }


    DB::beginTransaction();
    try {
      // تحديث الحقول المرتبطة بالنموذج
      $existingFieldIds = collect($request->fields)->pluck('id')->filter()->toArray();

      // حذف الحقول غير الموجودة في الطلب
      Form_Field::where('form_template_id', $request->id)->whereNotIn('id', $existingFieldIds)->delete();

      $done = $request->fields;
      // تحديث أو إضافة الحقول الجديدة مع ترتيبها حسب موقعها في المصفوفة
      foreach ($request->fields as $index => $field) {
        $data = [
          'name' => $field['name'],
          'label' => $field['label'],
          'type' => $field['type'],
          'required' => $field['required'],
          'value' => $field['value'],
          'driver_can' => $field['driver_can'],
          'customer_can' => $field['customer_can'],
          'ocr_prompt' => isset($field['ocr_prompt']) ? $field['ocr_prompt'] : null,
          'is_auto_filled' => isset($field['is_auto_filled']) ? $field['is_auto_filled'] : 0,
          'order' => $index + 1, // ترتيب الحقل حسب موقعه في المصفوفة (يبدأ من 1)
        ];
        if (isset($field['id'])) {
          $done = Form_Field::where('id', $field['id'])->update($data);
        } else {
          $data['form_template_id'] = $request->id;
          $done = Form_Field::create($data);
        }
        if (!$done) {
          DB::rollBack();
          return response()->json(['status' => 2, 'error' => __('Error: can not save the Template fields')]);
        }
      }

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Template fields updated successfully'), 'data' =>  $done]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {
      $find = Form_Template::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => __('Can not find the selected Template')]);
      }
      if ($find->tasks->count() > 0) {
        return response()->json(['status' => 2, 'error' => __('You can not delete this Template because it has associated tasks')]);
      }

      if ($find->customers->count() > 0) {
        return response()->json(['status' => 2, 'error' => __('You can not delete this Template because it has associated customers')]);
      }

      if ($find->drivers->count() > 0) {
        return response()->json(['status' => 2, 'error' => __('You can not delete this Template because it has associated drivers')]);
      }

      if ($find->users->count() > 0) {
        return response()->json(['status' => 2, 'error' => __('You can not delete this Template because it has associated users')]);
      }

      if ($find->fields) {
        foreach ($find->fields as $field) {
          $field->delete();
        }
      }

      $done =  $find->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error to delete The selected Template')]);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Template deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  /**
   * Duplicate pricing methods and their parameters
   *
   * @param Pricing_Template $originalPricingTemplate
   * @param Pricing_Template $newPricingTemplate
   */
  private function duplicatePricingMethods($originalPricingTemplate, $newPricingTemplate)
  {
    foreach ($originalPricingTemplate->pricing_methods as $pricingMethod) {
      // Create new pricing method
      $newPricingMethod = Pricing::create([
        'status' => $pricingMethod->status,
        'pricing_template_id' => $newPricingTemplate->id,
        'pricing_method_id' => $pricingMethod->pricing_method_id
      ]);

      // Duplicate pricing parameters
      foreach ($pricingMethod->parametars as $parameter) {
        Pricing_Parametar::create([
          'from_val' => $parameter->from_val,
          'to_val' => $parameter->to_val,
          'price' => $parameter->price,
          'pricing_id' => $newPricingMethod->id
        ]);
      }
    }
  }

  /**
   * Duplicate geofence pricing configurations
   *
   * @param Pricing_Template $originalPricingTemplate
   * @param Pricing_Template $newPricingTemplate
   */
  private function duplicateGeofencePricing($originalPricingTemplate, $newPricingTemplate)
  {
    foreach ($originalPricingTemplate->geoFences as $geofence) {
      Pricing_Geofence::create([
        'type' => $geofence->type,
        'amount' => $geofence->amount,
        'pricing_template_id' => $newPricingTemplate->id,
        'geofence_id' => $geofence->geofence_id
      ]);
    }
  }

  /**
   * Duplicate many-to-many relationships for pricing template
   * Note: We exclude customer relationships as they represent user data, not configuration
   *
   * @param Pricing_Template $originalPricingTemplate
   * @param Pricing_Template $newPricingTemplate
   */
  private function duplicatePricingRelationships($originalPricingTemplate, $newPricingTemplate)
  {
    // Duplicate tag relationships (configuration data)
    $tagIds = $originalPricingTemplate->tags->pluck('id')->toArray();
    if (!empty($tagIds)) {
      $newPricingTemplate->tags()->attach($tagIds);
    }

    // Duplicate vehicle size relationships (configuration data)
    $vehicleSizeIds = $originalPricingTemplate->sizes->pluck('id')->toArray();
    if (!empty($vehicleSizeIds)) {
      $newPricingTemplate->sizes()->attach($vehicleSizeIds);
    }

    // Note: We intentionally exclude customer relationships as they represent
    // user data assignments, not template configuration
  }
}
