<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Vehicle;
use App\Models\Vehicle_Size;
use App\Models\Vehicle_Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        'vehicle_id' => $item->vehicle_id,
        'vehicle' => $item->vehicle->name . '-' . $item->vehicle->en_name,
        'sizes' => $item->sizes->count(),
      ];
    });

    $data['sizes'] = $sizes->get()->map(function ($item) {
      return [
        'id' => $item->id,
        'name' => $item->name,
        'type_id' => $item->vehicle_type_id,
        'vehicle_id' => $item->type->vehicle_id,
        'type' => $item->type->name . '-' . $item->type->en_name,
        'vehicle' => $item->type->vehicle->name . '-' . $item->type->vehicle->en_name,
      ];
    });
    return response()->json(['status' => 1, 'data' => $data]);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'v_name' => 'required|unique:vehicles,name,'  . ($req->id ?? 0),
      'v_en_name' => 'required|unique:vehicles,en_name,'  . ($req->id ?? 0),
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    try {
      $data = [
        'name' => $req->v_name,
        'en_name' => $req->v_en_name,
      ];

      if ($req->filled('id')) {
        $find = Vehicle::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Vehicle')]);
        }
        $done = $find->update($data);
      } else {
        $done = Vehicle::create($data);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Vehicle')]);
      }
      return response()->json(['status' => 1, 'success' => __('Vehicle saved successfully')]);
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

      $data = [
        'name' => $req->name,
        'en_name' => $req->en_name,
        'vehicle_id' => $req->vehicle,
      ];

      if ($req->filled('id')) {
        $find = Vehicle_Type::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Vehicle type')]);
        }
        $done = $find->update($data);
      } else {
        $done = Vehicle_Type::create($data);
      }

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Vehicle type')]);
      }
      return response()->json(['status' => 1, 'success' => __('Vehicle type saved successfully')]);
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

      $data = [
        'name' => $req->name,
        'vehicle_type_id' => $req->type,
      ];

      if ($req->filled('id')) {
        $find = Vehicle_Size::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Vehicle type size')]);
        }
        $done = $find->update($data);
      } else {
        $done = Vehicle_Size::create($data);
      }

      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Vehicle type size')]);
      }
      return response()->json(['status' => 1, 'success' => __('Vehicle type size saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {

      $find = Vehicle::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => 'Error to find selected Vehicle']);
      }
      if ($find->types->count() !== 0) {
        return response()->json(['status' => 2, 'error' => 'you can not delete this Vehicle']);
      }
      $done = Vehicle::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Vehicle']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Vehicle deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function destroy_type(Request $req)
  {
    DB::beginTransaction();

    try {

      $find = Vehicle_Type::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => 'Error to find selected Vehicle Type']);
      }
      if ($find->sizes->count() !== 0) {
        return response()->json(['status' => 2, 'error' => 'you can not delete this Vehicle Type']);
      }
      $done = Vehicle_Type::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Vehicle Type']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Vehicle Type deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
  public function destroy_size(Request $req)
  {
    DB::beginTransaction();

    try {

      $find = Vehicle_Size::findOrFail($req->id);
      if (!$find) {
        return response()->json(['status' => 2, 'error' => 'Error to find selected Vehicle Size']);
      }
      if ($find->drivers->count() !== 0) {
        return response()->json(['status' => 2, 'error' => 'you can not delete this Vehicle Size']);
      }
      $done = Vehicle_Size::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Vehicle Size']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Vehicle Size deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function getTypes($vehicleId)
  {
    return Vehicle_Type::where('vehicle_id', $vehicleId)->get();
  }

  public function getSizes($typeId)
  {
    return Vehicle_Size::where('vehicle_type_id', $typeId)->get();
  }
}
