<?php

namespace App\Http\Controllers\customer;

use App\Helpers\IpHelper;
use Exception;
use Illuminate\Http\Request;
use App\Models\Customs_Clearance;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Customs_Clearance_Offer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomsClearanceController extends Controller
{

  function checkAgent()
  {
    if (!auth()->user()->is_customs_clearance_agent) {
      abort(404);
    }
  }
  public function index()
  {
    $this->checkAgent();
    return view('customers.customs-clearance.ads');
  }


  public function getData(Request $request)
  {
    $this->checkAgent();
    $size_id = auth()->user()->vehicle_size_id;
    $broker = auth()->user()->id;

    $query = Customs_Clearance::where('status', 'in_progress')->where('public', 1)->where('closed', 0)->where('clearance_agent_id', null);

    $query->orderBy('id', 'DESC');

    // إضافة التصفية عن طريق pagination مباشرة
    $data = $query->paginate(9); // 9 منتجات لكل صفحة

    // إضافة المعالجة المخصصة داخل صفحة البيانات
    $data->getCollection()->transform(function ($ad) use ($broker) {

      $brokerOffer = Customs_Clearance_Offer::where('customs_clearance_id', $ad->id)
        ->where('clearance_agent_id', $broker)
        ->first();

      // التحقق من وجود عرض مقبول
      $acceptedOffer = Customs_Clearance_Offer::where('customs_clearance_id', $ad->id)
        ->where('accepted', true)
        ->where('clearance_agent_id', $broker)
        ->first();

      return [
        'id' => $ad->id,
        'clearance_id' => $ad->id,
        'price' => $ad->total_price ?? 0,
        'note' => $ad->notes,
        'has_offer' => $brokerOffer ? true : false,
        'offer_price' => $brokerOffer ? $brokerOffer->price : null,
        'has_accepted_offer' => $acceptedOffer ? true : false,

        'customer' => [
          'name'   => $ad->owner->name,
          'phone'  => $ad->owner->phone,
          'email'  => $ad->owner->email,
          'image'  => $ad->owner->image,
        ],

      ];
    });

    return response()->json(['data' => $data, 'count' => $data->total()]);
  }

  public function show($id)
  {
    $this->checkAgent();
    $ad = Customs_Clearance::findOrFail($id);
    if ($ad->status !== 'in_progress' || $ad->closed === true || $ad->public === 0) {
      abort(404);
    }
    $broker = auth()->user()->id;

    // التحقق من وجود عرض مقبول
    $acceptedOffer = Customs_Clearance_Offer::where('customs_clearance_id', $id)
      ->where('accepted', true)
      ->first();
    $offer = Customs_Clearance_Offer::where('customs_clearance_id', $id)->where('clearance_agent_id', $broker)->first();

    return view('customers.customs-clearance.ads-show', compact('ad', 'offer', 'acceptedOffer'));
  }

  public function getOffers(Request $req)
  {
    $this->checkAgent();
    $offers = Customs_Clearance_Offer::where('customs_clearance_id', $req->id)->get();

    $transformed = $offers->map(function ($offer) {
      return [
        'id' => $offer->id,
        'broker' => $offer->broker,
        'broker_id' => $offer->clearance_agent_id,
        'price' => $offer->price,
        'accepted' => $offer->accepted,
        'description' => $offer->description,
      ];
    });

    return response()->json([
      'data' => $transformed,
      'count' => $transformed->count(),
    ]);
  }

  public function storeOffers(Request $req)
  {
    $this->checkAgent();
    $validator = Validator::make($req->all(), [
      'ad' => 'required|exists:customs_clearance,id',
      'price' => 'required|numeric',
      'description' => 'nullable|string|max:400',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
    }

    // التحقق من حالة الإعلان وإمكانية تقديم عرض
    $ad = Customs_Clearance::findOrFail($req->ad);
    if ($ad->status !== 'in_progress' || $ad->closed === true || $ad->public === 0) {
      return response()->json(['status' => 2, 'error' => 'Cannot submit offer for this advertisement.']);
    }
    $broker = auth()->user()->id;

    $existingOffer = Customs_Clearance_Offer::where('customs_clearance_id', $req->ad)
      ->where('clearance_agent_id', $broker)
      ->first();

    $acceptedOffer = Customs_Clearance_Offer::where('customs_clearance_id', $req->ad)
      ->where('accepted', true)
      ->first();

    try {
      $data = [
        'price' => $req->price,
        'description' => $req->description,
      ];

      if ($req->filled('id')) {
        $find = Customs_Clearance_Offer::findOrFail($req->id);
        $done = $find->update($data);
      } else {
        $data['customs_clearance_id'] = $req->ad;
        $data['clearance_agent_id'] = $broker;
        $done = Customs_Clearance_Offer::create($data);
      }
      if (!$done) {
        return response()->json(['status' => 2, 'error' => __('Error: can not save the Offer')]);
      }
      return response()->json(['status' => 1, 'success' => __('Offer saved successfully')]);
    } catch (Exception $ex) {
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }

  public function assignTaskByOffer($id)
  {
    $this->checkAgent();
    DB::beginTransaction();
    try {
      $data = Customs_Clearance_Offer::find($id);
      $user = auth()->user();
      if (!$user || $user->id !== $data->clearance_agent_id) {
        return response()->json([
          'status' => 2,
          'error' => __('You do not have permission to do actions to this record')
        ]);
      }
      if (!$data->accepted) {
        return response()->json([
          'status' => 2,
          'error' => __('This Offer is no accepted yet')
        ]);
      }

      $data_ad = $data->customsClearance;
      if ($data_ad->status !== 'in_progress') {
        return response()->json([
          'status' => 2,
          'error' => __('This Customs Clearance Ad Already Closed')
        ]);
      }

      $userIp = IpHelper::getUserIpAddress();
      $history = [
        [
          'action_type' => 'assign',
          'description' => 'assign Customs Clearance By Customs Clearance Ad Offers',
          'ip' => $userIp,
          'clearance_agent_id' => $user->id
        ]
      ];

      $data_ad->clearance_agent_id = $user->id;
      $data_ad->status = 'assign';
      $data_ad->public = false;
      $data_ad->total_price = $data->price;

      $broker = Customer::findOrFail($user->id);
      if ($broker->is_customs_clearance_agent != 1) {
        DB::rollBack();
        return response()->json(['status' => 2, 'error' => __('The Selected Broker not found')]);
      }

      $totalPrice = $data->price;
      $pricing = $data_ad->pricing;

      $vat = floatval($pricing->vat_commission); // تحويل إلى رقم والتعامل مع null
      $commission = floatval($pricing->service_commission);
      $commissionType = $pricing->service_commission_type; // 'fixed' or 'percentage'

      $commissionAmount = 0;
      $vatAmount = 0;

      // حساب العمولة فقط إذا كانت قيمة صالحة
      if ($commission > 0) {
        if ($commissionType === 'percentage') {
          $commissionAmount = ($commission / 100) * $totalPrice;
        } else { // fixed
          $commissionAmount = $commission;
        }
      }

      // حساب الضريبة فقط إذا كانت قيمة صالحة
      $priceWithCommission = $totalPrice + $commissionAmount;
      if ($vat > 0) {
        $vatAmount = ($vat / 100) * $priceWithCommission;
      }

      // المجموع النهائي
      $totalAmount = $commissionAmount + $vatAmount;

      $data_ad->commission = $totalAmount;
      $data_ad->commission_type = 'dynamic';
      $data_ad->history()->createMany($history);
      $data_ad->save();


      DB::commit();
      return response()->json(['status' => 1, 'success' => __('Customs Clearance assigned successfully')]);
    } catch (Exception $ex) {
      DB::rollBack();
      return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
    }
  }
}
