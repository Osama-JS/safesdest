<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TagsController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:tags_settings', ['only' => ['index', 'getData', 'edit', 'store', 'destroy']]);
  }
  public function index()
  {
    return view('admin.settings.tags');
  }

  public function getData(Request $request)
  {
    $columns = [
      1 => 'id',
      2 => 'name',
      3 => 'slug',
      4 => 'description',
      5 => 'drivers',
      6 => 'customers',
    ];

    $totalData = Tag::count();
    $totalFiltered = $totalData;

    $limit  = $request->input('length');
    $start  = $request->input('start');
    $order  = $columns[$request->input('order.0.column')] ?? 'id';
    $dir    = $request->input('order.0.dir') ?? 'desc';

    $search = $request->input('search');

    $query = Tag::query();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('id', 'LIKE', "%{$search}%")
          ->orWhere('name', 'LIKE', "%{$search}%")
          ->orWhere('slug', 'LIKE', "%{$search}%");
      });
    }


    $totalFiltered = $query->count();


    $tags = $query
      ->offset($start)
      ->limit($limit)
      ->orderBy($order, $dir)
      ->get();

    $data = [];
    $fakeId = $start;

    foreach ($tags as $val) {
      $data[] = [
        'id'         => $val->id,
        'fake_id'    => ++$fakeId,
        'name'       => $val->name,
        'slug'       => $val->slug,
        'description'      => $val->description ?? '',
        'drivers'    => $val->drivers->count(),
        'customers'  => $val->customers->count(),
      ];
    }

    return response()->json([
      'draw'            => intval($request->input('draw')),
      'recordsTotal'    => $totalData,
      'recordsFiltered' => $totalFiltered,
      'code'            => 200,
      'data'            => $data,
    ]);
  }

  public function edit($id)
  {
    $data = Tag::findOrFail($id);

    return response()->json($data);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:tags,name,'  . ($req->id ?? 0),
      'slug' => 'required|unique:tags,slug,'  . ($req->id ?? 0),
      'description' => 'nullable|string|max:400',
    ], [
      'name.required' => __('The tag name is required.'),
      'name.unique' => __('The tag name has already been taken.'),
      'slug.required' => __('The tag slug is required.'),
      'slug.unique' => __('The tag slug has already been taken.'),
      'description.string' => __('The description must be a string.'),
      'description.max' => __('The description may not be greater than 400 characters.'),
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    try {
      $data = [
        'name' => $req->name,
        'slug' => $req->slug,
        'description' => $req->description ?? null,
      ];

      if ($req->filled('id')) {
        $find = Tag::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Tag')]);
        }
        $done = $find->update($data);
      } else {
        $done = Tag::create($data);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Tag')]);
      }
      return response()->json(['status' => 1, 'success' => __('Tag saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {

      $find = Tag::findOrFail($req->id);
      if ($find->drivers->count() > 0 || $find->customers->count() > 0 || $find->pricing->count() > 0) {
        return response()->json(['status' => 2, 'error' => __('Error to find selected Tag')]);
      }

      $done = $find->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error to delete Tag')]);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Tag deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
