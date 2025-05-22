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

  public function __construct()
  {
    $this->middleware('permission:geo_fence_settings', ['only' => ['index', 'getData', 'edit', 'store', 'destroy']]);
  }

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
        $q->where('description', 'LIKE', '%' . $search . '%');
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
    ], [
      'name.required' => __('The geofence name is required.'),
      'name.unique' => __('The geofence name has already been taken.'),
      'description.string' => __('The description must be a string.'),
      'coordinates.required' => __('The coordinates field is required.'),
      'coordinates.string' => __('The coordinates must be a string.'),
      'teams.array' => __('Teams must be an array.'),
    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    DB::beginTransaction();
    try {
      $data = [
        'name' => $req->name,
        'description' => $req->description,
        'coordinates' => DB::raw("ST_GeomFromText('{$req->coordinates}', 4326)")
      ];

      if ($req->filled('id')) {
        $find = Geofence::findOrFail($req->id);
        if (!$find) {
          return response()->json(['status' => 2, 'error' => __('Can not find the selected Geo-Fence')]);
        }
        $done = $find->update($data);
        $find->teams()->delete();
        $teams = collect($req->teams)->filter()->map(function ($teamId) {
          return ['team_id' => $teamId];
        })->toArray();
        $done = $find->teams()->createMany($teams);
      } else {
        $geo = Geofence::create($data);
        $teams = collect($req->teams)->filter()->map(function ($teamId) {
          return ['team_id' => $teamId];
        })->toArray();
        $done = $geo->teams()->createMany($teams);
      }
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('error to save Geo-Fence')]);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Geo-Fence saved successfully')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function edit($id): JsonResponse
  {
    $data = Geofence::findOrFail($id);
    $data->teamsIds = $data->teams()->pluck('team_id');

    return response()->json($data);
  }

  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {

      $done = Geofence::where('id', $req->id)->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('Error to delete Geo-fence')]);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Geo-fence deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
