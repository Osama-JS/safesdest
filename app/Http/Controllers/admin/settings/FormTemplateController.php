<?php

namespace App\Http\Controllers\admin\settings;

use Illuminate\Http\Request;
use App\Models\Form_Template;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FormTemplateController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:templates_settings', ['only' => ['index', 'getData', 'create',  'store']]);
  }

  public function index()
  {
    return view('admin.settings.form_template.index');
  }

  public function getData(Request $request)
  {
    $query = Form_Template::query();

    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'ILIKE', '%' . $search . '%');
      });
    }
    $query->orderBy('id', 'DESC');

    $count = $query->count();

    $products = $query->paginate(10);

    return response()->json(['data' => $products, 'count' => $count]);
  }

  public function create()
  {
    return view('admin.settings.form_template.create');
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:form_templates,name',
      'description' => 'required',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    Form_Template::create([
      'name' => $req->name,
      'description' => $req->description,
    ]);
    return response()->json(['status' => 1, 'msg' => 'Form Template Created Successfully']);
  }
}
