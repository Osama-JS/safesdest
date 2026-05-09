<?php

namespace App\Http\Controllers\admin;

use Exception;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\InvestmentContract;
use App\Models\InvestorWallet;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class InvestorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_investors', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:save_investors', ['only' => ['store']]);
        $this->middleware('permission:delete_investors', ['only' => ['destroy']]);
    }

    public function index()
    {
        $customers = Customer::where('status', 'active')->get();
        return view('admin.investors.index', compact('customers'));
    }

    public function getData(Request $request)
    {
        $columns = [
            1 => 'id',
            2 => 'name',
            3 => 'email',
            4 => 'phone',
            5 => 'status',
        ];

        $query = User::where('investor', true)->where('status', '!=', 'deleted');

        $totalData = $query->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $query->count();
        }

        $investors = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->with(['activeInvestmentContract', 'investorWallet'])
            ->get();

        $data = [];
        $ids = $start;

        foreach ($investors as $investor) {
            $contract = $investor->activeInvestmentContract;
            $nestedData['id'] = $investor->id;
            $nestedData['fake_id'] = ++$ids;
            $nestedData['name'] = $investor->name;
            $nestedData['email'] = $investor->email;
            $nestedData['phone'] = $investor->phone;
            $nestedData['status'] = $investor->status;
            $nestedData['reset_password'] = $investor->reset_password;
            $nestedData['wallet_balance'] = $investor->investorWallet->balance ?? 0;
            $nestedData['contract_type'] = $contract ? ($contract->contract_type == 'task_investment' ? 'بالمهام' : 'عام') : 'لا يوجد';
            $nestedData['commission'] = $contract ? $contract->commission_value . ($contract->commission_type == 'percentage' ? '%' : ' ثابت') : '-';

            $data[] = $nestedData;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
            'summary' => [
                'total' => User::where('investor', true)->where('status', '!=', 'deleted')->count(),
                'active' => User::where('investor', true)->where('status', 'active')->count(),
                'task_based' => InvestmentContract::where('status', 'active')->where('contract_type', 'task_investment')->count(),
                'general_based' => InvestmentContract::where('status', 'active')->where('contract_type', 'general_investment')->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($request->id ?? 0),
            'phone' => 'nullable|string|max:20',
            'phone_code' => 'required|string|max:10',
            'password' => $request->id ? 'nullable|min:6|confirmed' : 'required|min:6|confirmed',
            'status' => 'required|in:active,inactive,pending',
            // Contract rules
            'contract_type' => 'required|in:task_investment,general_investment',
            'commission_type' => 'required|in:percentage,fixed',
            'commission_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'customer_ids' => 'nullable|array',
            'min_commission_threshold' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            $investorRole = Role::where('name', 'Investor')->first();
            if (!$investorRole) {
                // Fallback to Owner if Investor role doesn't exist for some reason
                $investorRole = Role::where('name', 'Owner')->first();
            }

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'phone_code' => $request->phone_code,
                'status' => $request->status,
                'investor' => true,
                'role_id' => $investorRole ? $investorRole->id : null,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            if ($request->id) {
                $investor = User::findOrFail($request->id);
                $investor->update($data);
            } else {
                $investor = User::create($data);
                // Create wallet for new investor
                InvestorWallet::create([
                    'user_id' => $investor->id,
                    'balance' => 0,
                    'credit' => 0,
                    'debit' => 0,
                ]);
            }

            // Close old contracts if any and create new one if data changed
            // For simplicity, we'll just update or create the active one
            InvestmentContract::updateOrCreate(
                ['user_id' => $investor->id, 'status' => 'active'],
                [
                    'contract_type' => $request->contract_type,
                    'commission_type' => $request->commission_type,
                    'commission_value' => $request->commission_value,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'filter_customer_ids' => $request->customer_ids,
                    'min_commission_threshold' => $request->min_commission_threshold,
                    'created_by' => auth()->id(),
                ]
            );

            DB::commit();
            return response()->json(['status' => 1, 'success' => 'تم حفظ بيانات المستثمر والعقد بنجاح']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $investor = User::with([
            'activeInvestmentContract', 
            'investorWallet', 
            'userWallet',
            'investmentContracts' => function($q) {
                $q->orderBy('created_at', 'desc')->limit(5);
            }
        ])->findOrFail($id);
        
        return response()->json($investor);
    }

    public function resetPass(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);
            $status = $user->reset_password == 1 ? 0 : 1;
            $user->update(['reset_password' => $status]);
            
            return response()->json(['status' => 1, 'success' => $status]);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $investor = User::findOrFail($request->id);
            // Mark as deleted instead of actual delete to preserve financial records
            $investor->update(['status' => 'deleted']);
            return response()->json(['status' => 1, 'success' => 'تم حذف المستثمر بنجاح']);
        } catch (Exception $e) {
            return response()->json(['status' => 2, 'error' => $e->getMessage()]);
        }
    }
}
