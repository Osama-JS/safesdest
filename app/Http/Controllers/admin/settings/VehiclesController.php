<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Vehicle;
use App\Models\Vehicle_Size;
use App\Models\Vehicle_Type;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class VehiclesController extends Controller
{
  public function index()
  {
    return view('admin.settings.vehicles');
  }

  public function getData(Request $req)
  {
    $vehicles = Vehicle::with('types.sizes')->get();

    $types = Vehicle_Type::with('sizes');
    if ($req->has('vehicle') && !empty($req->vehicle)) {
      $types->where('vehicle_id', $req->vehicle);
    }

    $sizes = Vehicle_Size::with('type.vehicle');
    if ($req->has('type') && !empty($req->type)) {
      $sizes->where('vehicle_type_id', $req->type);
    }
    $data = [];
    $data['vehicles'] = $vehicles->map(function ($item) {
      return [
        'id' => $item->id,
        'name' => $item->name,
        'en_name' => $item->en_name,
        'types' => $item->types->count(),
      ];
    });

    $data['types'] = $types->get()->map(function ($item) {
      return [
        'id' => $item->id,
        'name' => $item->name,
        'en_name' => $item->en_name,
        'vehicle' => $item->vehicle->name . '-' . $item->vehicle->en_name,
        'sizes' => $item->sizes->count(),
      ];
    });

    $data['sizes'] = $sizes->get()->map(function ($item) {
      return [
        'id' => $item->id,
        'name' => $item->name,
        'type' => $item->type->name . '-' . $item->type->en_name,
        'vehicle' => $item->type->vehicle->name . '-' . $item->type->vehicle->en_name,
      ];
    });
    return response()->json(['status' => 1, 'data' => $data]);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'v_name' => 'required|unique:vehicles,name,' .  ($req->id ?? 0),
      'v_en_name' => 'required|unique:vehicles,en_name,' .  ($req->id ?? 0),
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    try {
      if (isset($req->id) && !empty($req->id)) {
        $find = Vehicle::where('id', $req->id)->first();
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Vehicle not found')]);
        }
        $done = Vehicle::where('id', $req->id)->update([
          'name' => $req->v_name,
          'en_name' => $req->v_en_name,
        ]);
      } else {
        $done = Vehicle::create([
          'name' => $req->v_name,
          'en_name' => $req->v_en_name,
        ]);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('error to save Vehicle')]);
      }
      return response()->json(['status' => 1, 'success' => __('Vehicle saved')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function store_type(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required',
      'en_name' => 'required',
      'vehicle' => 'required|exists:vehicles,id',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    try {
      if (isset($req->id) && !empty($req->id)) {
        $find = Vehicle_Type::where('id', $req->id)->first();
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Vehicle type not found')]);
        }
        $done = Vehicle_Type::where('id', $req->id)->update([
          'name' => $req->name,
          'en_name' => $req->en_name,
          'vehicle_id' => $req->vehicle,
        ]);
      } else {
        $done = Vehicle_Type::create([
          'name' => $req->name,
          'en_name' => $req->en_name,
          'vehicle_id' => $req->vehicle,
        ]);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('error to save Vehicle type')]);
      }
      return response()->json(['status' => 1, 'success' => __('Vehicle type saved')]);
    } catch (QueryException $ex) {
      if ($ex->getCode() == 23505) {
        return response()->json(['status' => 2, 'error' => __('This vehicle type already exists')]);
      }
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function store_size(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required',
      'type' => 'required|exists:vehicle_types,id',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    try {
      if (isset($req->id) && !empty($req->id)) {
        $find = Vehicle_Size::where('id', $req->id)->first();
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Vehicle type not found')]);
        }
        $done = Vehicle_Size::where('id', $req->id)->update([
          'name' => $req->name,
          'vehicle_type_id' => $req->type,
        ]);
      } else {
        $done = Vehicle_Size::create([
          'name' => $req->name,
          'vehicle_type_id' => $req->type,
        ]);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('error to save Vehicle type size')]);
      }
      return response()->json(['status' => 1, 'success' => __('Vehicle type size saved')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
