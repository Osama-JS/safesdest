<?php

namespace App\Http\Controllers\admin;

use Exception;

use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class TeamsController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:view_teams', ['only' => ['index', 'getData', 'edit']]);
    $this->middleware('permission:save_teams', ['only' => ['store']]);
    $this->middleware('permission:delete_teams', ['only' => ['destroy']]);
    $this->middleware('permission:details_teams', ['only' => ['']]);
  }


  public function index()
  {
    $teams = Teams::paginate(8);
    return view('admin.teams.index', compact('teams'));
  }


  public function getData(Request $request)
  {
    $query = Teams::with('drivers');

    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'ILIKE', '%' . $search . '%')
          ->orwhere('id', 'ILIKE', '%' . $search . '%');
      });
    }
    $query->orderBy('id', 'DESC');

    $count = $query->count();

    // الإرجاع مع Pagination
    $products = $query->paginate(9); // 20 منتج لكل صفحة

    return response()->json(['data' => $products, 'count' => $count]);
  }

  public function store(Request $req)
  {
    $validator = Validator::make($req->all(), [
      'id' => 'nullable|exists:teams,id',
      'name' => 'required|unique:teams,name,' .  ($req->id ?? 0),
      'address' => 'required',
      'commission_type' => 'nullable|in:fixed,rate,subscription',
      'commission' => 'required_with:commission_type|min:0',

    ]);
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }
    DB::beginTransaction();
    try {
      if (isset($req->id) && !empty($req->id)) {
        $done = Teams::find($req->id)->update([
          'name' => $req->name,
          'address' => $req->address,
          'team_commission_type' =>   $req->commission_type,
          'team_commission_value' =>  $req->commission,
          'location_update_interval' => $req->location_update,
          'note' =>  $req->note
        ]);
      } else {

        $done = Teams::create([
          'name' => $req->name,
          'address' => $req->address,
          'team_commission_type' =>   $req->commission_type,
          'team_commission_value' =>  $req->commission,
          'location_update_interval' => $req->location_update ?? 30,
          'note' =>  $req->note
        ]);
      }

      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('error to save team')]);
      }

      DB::commit();
      return response()->json(['status' => 1, 'success' => __('teams saved')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function edit($id): JsonResponse
  {
    $team = Teams::findOrFail($id);
    return response()->json($team);
  }


  public function destroy(Request $req)
  {
    DB::beginTransaction();

    try {

      $team = Teams::findOrFail($req->id);
      if ($team->drivers->count() > 0) {
        return response()->json(['status' => 2, 'error' => 'You cannot delete this team because it has associated drivers']);
      }
      $done = $team->delete();
      if (!$done) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => 'Error to delete team']);
      }
      DB::commit();
      return response()->json(['status' => 1, 'success' => __('team deleted')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
