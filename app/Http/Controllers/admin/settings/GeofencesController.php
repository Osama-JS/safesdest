<?php

namespace App\Http\Controllers\admin\settings;

use Exception;
use App\Models\Teams;
use App\Models\Geofence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;


class GeofencesController extends Controller
{
  public function index()
  {
    $data = Teams::all();
    return view('admin.settings.geofences', compact('data'));
  }

  public function getData(Request $request)
  {
    $query = Geofence::query();

    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'LIKE', '%' . $search . '%');
      });
    }
    $query->orderBy('id', 'DESC')->get();

    $data = $query->orderBy('id', 'DESC')->get()->map(function ($geofence) {
      return [
        'id' => $geofence->id,
        'name' => $geofence->name,
        'description' => $geofence->description,
        'geometry' => $geofence->coordinates_wkt
      ];
    });

    return response()->json(['status' => 1, 'data' => $data]);
  }



  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'name' => 'required|unique:geofences,name,' . ($req->id ?? 0),
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
          'coordinates' => DB::raw("ST_GeomFromText('{$req->coordinates}', 4326)")
        ]);
      } else {
        $done = Geofence::create([
          'name' => $req->name,
          'description' => $req->description,
          'coordinates' => DB::raw("ST_GeomFromText('{$req->coordinates}', 4326)")
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

  public function edit($id): JsonResponse
  {
    $data = Geofence::findOrFail($id);
    return response()->json($data);
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {

      $done = Geofence::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete Geo-fence']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Geo-fence deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
