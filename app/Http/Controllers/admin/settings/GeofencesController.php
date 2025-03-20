<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Teams;
use App\Models\Geofence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class GeofencesController extends Controller
{
  public function index()
  {
    $data = Teams::all();
    return view('admin.settings.geofences', compact('data'));
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:geofences,name',
      'description' => 'nullable|string',
      'coordinates' => 'required|string',
      'teams' => 'nullable|array',
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    try {
      if (isset($req->id) && !empty($req->id)) {
        $find = Geofence::where('id', $req->id)->first();
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Geo-Fence not found')]);
        }
        $done = Geofence::where('id', $req->id)->update([
          'name' => $req->name,
          'description' => $req->description,
          'coordinates' => DB::raw("ST_GeomFromText(?)", [$req->coordinates]),
        ]);
      } else {
        $done = Geofence::create([
          'name' => $req->name,
          'description' => $req->description,
          'coordinates' => DB::raw("ST_GeomFromText(?)", [$req->coordinates]),
        ]);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('error to save Geo-Fence')]);
      }
      return response()->json(['status' => 1, 'success' => __('Geo-Fence saved')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
