<?php

namespace App\Http\Controllers\admin;

use Exception;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\Team;
use App\Models\Team_Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Team_Wallet_Transaction;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FunctionsController;
use App\Models\Teams;
use App\Helpers\FileHelper;
use App\Models\TeamWalletPaymentRequestLog;
use App\Helpers\IpHelper;

class TeamWalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:wallet_teams', ['only' => ['index']]);
        $this->middleware('permission:wallet_mange_teams', ['only' => ['editTransaction', 'storeTransaction', 'destroy']]);
    }

    public function index($id, $name)
    {
        $team = Teams::find($id);
        if (!$team) {
            abort(404);
        }
        $data = Team_Wallet::where('team_id', $team->id)->first();
        if (!$data) {
            $data = new Team_Wallet();
            $data->team_id = $id;
            $data->save();
        }
        return view('admin.teams.wallets.index', compact('data'));
    }

    public function getDataTransactions(Request $request)
    {

        $columns = [
          1 => 'id',
          2 => 'amount',
          3 => 'description',
          4 => 'maturity',
          5 => 'task',
          6 => 'user',
          7 => 'created_at',
        ];

        $wallet = $request->input('wallet');
        $fromDate  = $request->input('from_date');
        $toDate    = $request->input('to_date');
        $search = $request->input('search');
        $type = $request->input('status');


        $totalData = Team_Wallet_Transaction::where('team_wallet_id', $wallet)->count();
        $totalFiltered = $totalData;

        $limit  = $request->input('length');
        $start  = $request->input('start');
        $order  = $columns[$request->input('order.0.column')] ?? 'id';
        $dir    = $request->input('order.0.dir') ?? 'desc';


        $query = Team_Wallet_Transaction::query();
        $query->where('team_wallet_id', $wallet);

        if ($fromDate && $toDate) {
            $query->whereBetween('created_at', [
              Carbon::parse($fromDate)->startOfDay(),
              Carbon::parse($toDate)->endOfDay()
            ]);
        }


        if (!empty($search->value)) {
            $query->where(function ($q) use ($search) {
                $q->where('sequence', 'LIKE', "%" . $search . "%")->orWhere('description', 'LIKE', "%" . $search . "%");
                $q->orWhere('amount', 'LIKE', "%" . $search . "%");
            });
        }

        if (!empty($type) && $type != 'all') {
            $query->where('transaction_type', $type);
        }

        $totalFiltered = $query->count();
        $wallets = $query
          ->with(['user', 'task']) // Eager load relationships
          ->offset($start)
          ->limit($limit)
          ->orderBy($order, $dir)
          ->get();

        $data = [];
        $fakeId = $start;

        foreach ($wallets as $val) {
            $data[] = [
              'id'         => $val->id,
              'fake_id'    => ++$fakeId,
              'amount'     => (float) $val->amount,
              'type'       => $val->transaction_type,
              'description' => $val->description ?? '',
              'maturity'    => $val->maturity_time ? $val->maturity_time : '',
              'user'        => $val->user ? $val->user->name : 'automatic',
              'task'        => $val->task_id ? $val->task_id : '',
              'image'       => $val->image ?? '',
              'sequence'    => $val->sequence,
              'status'      => (int) $val->status, // Ensure it's integer
              'created_at'  => $val->created_at->format('Y-m-d H:i'),
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

    public function editTransaction($id)
    {
        $data = Team_Wallet_Transaction::findOrFail($id);
        if (!$data) {
            return response()->json(['status' => 2, 'error' => __('Can not find the selected Transaction')]);
        }
        return response()->json(['status' => 1, 'data' => $data]);
    }

    public function storeTransaction(Request $req)
    {
        $validator = Validator::make($req->all(), [
          'amount' => 'required|numeric|min:0.01|gt:0',
          'description' => 'required|string|max:255',
          'type' => 'required|in:credit,debit',
          'wallet' => 'required|exists:team_wallet,id',
          'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:4096',
          'maturity' => 'nullable|date',
          'task_id' => 'nullable|exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
              'status' => 0,
              'error'  => $validator->errors()
            ]);
        }

        try {
            $wallet = Team_Wallet::findOrFail($req->wallet);
            $adjustedBalance = $wallet->balance;
            $existingTransaction = null;

            // التعديل على معاملة سابقة
            if ($req->filled('id')) {
                $existingTransaction = Team_Wallet_Transaction::findOrFail($req->id);

                // إرجاع المبلغ القديم للحساب
                if ($existingTransaction->transaction_type === 'credit') {
                    $adjustedBalance -= $existingTransaction->amount;
                } elseif ($existingTransaction->transaction_type === 'debit') {
                    $adjustedBalance += $existingTransaction->amount;
                }
            }



            DB::beginTransaction();

            $data = [
              'amount' => $req->amount,
              'description' => $req->description,
              'transaction_type' => $req->type,
              'maturity_time' => $req->type === 'credit' ? null : $req->maturity,
            ];

            if ($req->hasFile('image')) {
                $data['image'] = (new FunctionsController())->convert($req->image, 'wallets/team/transactions');
                $data['image'] = FileHelper::uploadFile($req->file("image"), 'wallets/team/transactions');

            }

            $oldImage = null;

            if ($existingTransaction) {
                $user = Auth::user();

                if ($existingTransaction->task_id && $user->role_id !== 1) {
                    DB::rollBack();
                    return response()->json([
                      'status' => 2,
                      'error'  => __('You can not edit this transaction')
                    ]);
                }


                if ($req->hasFile('image') && $existingTransaction->image) {
                    $oldImage = $existingTransaction->image;
                }

                $existingTransaction->update($data);
            } else {
                $data['team_wallet_id'] = $req->wallet;
                $data['user_id'] = auth()->id();
                $data['task_id'] = $req->task_id;
                Team_Wallet_Transaction::create($data);
            }

            if ($oldImage && file_exists($oldImage)) {
                // unlink($oldImage);
                FileHelper::deleteFileIfExists($oldImage);

            }

            DB::commit();

            return response()->json(['status' => 1, 'success' => __('Transaction Saved successfully')]);
        } catch (\Exception $ex) {
            DB::rollBack();

            return response()->json([
              'status' => 2,
              'error'  => __('Error creating transaction: ') . $ex->getMessage()
            ]);
        }
    }


    public function destroy(Request $req)
    {
        DB::beginTransaction();
        try {
            $find = Team_Wallet_Transaction::findOrFail($req->id);
            if ($find->task_id) {
                return response()->json([
                  'status' => 2,
                  'error'  => __('You can not delete this transaction')
                ]);
            }
            $oldImage = null;
            if ($find->image) {
                $oldImage = $find->image;
            }
            $done = $find->delete();
            if ($oldImage) {
                // unlink($oldImage);
                FileHelper::deleteFileIfExists($oldImage);

            }

            if (!$done) {
                DB::rollBack();
                return response()->json(['status' => 2, 'error' => 'Error to delete Transaction']);
            }
            DB::commit();
            return response()->json(['status' => 1, 'success' => __('Transaction deleted')]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    /**
     * Log team payment request
     */
    public function logTeamPaymentRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_wallet_id' => 'required|exists:team_wallet,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_request_number' => 'required|string|max:50',
            'payment_method' => 'required|in:bank_transfer,other',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban_number' => 'nullable|string|max:34',
            'other_payment_method' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'selected_tasks' => 'nullable|array',
            'selected_tasks.*' => 'integer|exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'error' => $validator->errors()
            ]);
        }

        try {
            $teamWallet = Team_Wallet::with('team')->findOrFail($request->team_wallet_id);
            $team = $teamWallet->team;

            // Check if team has a leader
            if (!$team->hasTeamLeader()) {
                return response()->json([
                    'status' => 0,
                    'error' => __('Cannot create cash withdrawal request - Team does not have a team leader')
                ]);
            }

            $teamLeader = $team->teamLeader;

            // معالجة طريقة الدفع والملاحظات
            $finalNotes = $request->notes ?? '';

            // إضافة معلومات طريقة الدفع
            if ($request->payment_method === 'other' && $request->other_payment_method) {
                if (!empty($finalNotes)) {
                    $finalNotes .= "\n\n";
                }
                $finalNotes .= "طريقة الدفع: " . $request->other_payment_method;
            } elseif ($request->payment_method === 'bank_transfer') {
                $bankInfo = [];
                if ($request->bank_name) {
                    $bankInfo[] = "البنك: " . $request->bank_name;
                }
                if ($request->account_number) {
                    $bankInfo[] = "رقم الحساب: " . $request->account_number;
                }
                if ($request->iban_number) {
                    $bankInfo[] = "الآيبان: " . $request->iban_number;
                }

                if (!empty($bankInfo)) {
                    if (!empty($finalNotes)) {
                        $finalNotes .= "\n\n";
                    }
                    $finalNotes .= "معلومات التحويل البنكي:\n" . implode("\n", $bankInfo);
                }
            }

            // معالجة المهام المحددة
            if ($request->selected_tasks && count($request->selected_tasks) > 0) {
                // التحقق من أن المهام تنتمي للفريق
                $tasks = Task::whereIn('id', $request->selected_tasks)
                    ->where('team_id', $team->id)
                    ->with(['pickup', 'delivery'])
                    ->get();

                if ($tasks->count() !== count($request->selected_tasks)) {
                    return response()->json([
                        'status' => 0,
                        'error' => 'بعض المهام المحددة لا تنتمي لهذا الفريق'
                    ]);
                }

                // إضافة معلومات المهام للملاحظات
                if (!empty($finalNotes)) {
                    $finalNotes .= "\n\n";
                }

                $finalNotes .= "المهام المحددة:\n";
                foreach ($tasks as $task) {
                    $pickupAddress = $task->pickup->address ?? 'عنوان غير محدد';
                    $finalNotes .= "- مهمة #{$task->id}: {$pickupAddress} - {$task->total_price} ريال - {$task->status}\n";
                }
            }

            // Create log entry
            TeamWalletPaymentRequestLog::create([
                'team_wallet_id' => $request->team_wallet_id,
                'user_id' => auth()->id(),
                'team_id' => $team->id,
                'team_leader_id' => $teamLeader->id,
                'amount' => $request->amount,
                'payment_request_number' => $request->payment_request_number,
                'notes' => $finalNotes,
                'ip_address' => IpHelper::getUserIpAddress(),
                'printed_at' => now(),
            ]);

            return response()->json([
                'status' => 1,
                'success' => __('Your cash withdrawal request has been successfully registered')
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 2,
                'error' => __('Error registering withdrawal request') .' : ' . $ex->getMessage()
            ]);
        }
    }

    /**
     * Get team payment request logs
     */
    public function getTeamPaymentRequestLogs(Request $request)
    {
        $teamWalletId = $request->input('team_wallet_id');

        if (!$teamWalletId) {
            return response()->json([
                'status' => 0,
                'error' => 'معرف محفظة الفريق مطلوب'
            ]);
        }

        try {
            $logs = TeamWalletPaymentRequestLog::with(['user', 'teamLeader'])
                ->forTeamWallet($teamWalletId)
                ->orderBy('printed_at', 'desc')
                ->limit(10)
                ->get();

            $formattedLogs = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'amount' => $log->formatted_amount,
                    'payment_request_number' => $log->payment_request_number ?? '',
                    'notes' => $log->notes ?? '',
                    'user_name' => $log->user->name ?? '',
                    'team_leader_name' => $log->teamLeader->name ?? '',
                    'ip_address' => $log->ip_address ?? '',
                    'printed_at' => $log->formatted_printed_at,
                ];
            });

            return response()->json([
                'status' => 1,
                'data' => $formattedLogs
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 2,
                'error' => 'خطأ في جلب السجلات: ' . $ex->getMessage()
            ]);
        }
    }

    /**
     * Generate team payment PDF
     */
    public function generateTeamPaymentPDF(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_wallet_id' => 'required|exists:team_wallet,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'error' => $validator->errors()
            ]);
        }

        try {
            $teamWallet = Team_Wallet::with('team')->findOrFail($request->team_wallet_id);
            $team = $teamWallet->team;

            // Check if team has a leader
            if (!$team->hasTeamLeader()) {
                return response()->json([
                    'status' => 0,
                    'error' => 'لا يمكن إنشاء طلب سحب نقدي - الفريق لا يملك رئيس فريق'
                ]);
            }

            $teamLeader = $team->getTeamLeaderWithBankDetails();

            if (!$teamLeader) {
                return response()->json([
                    'status' => 0,
                    'error' => 'بيانات البنك غير مكتملة لرئيس الفريق'
                ]);
            }

            // Generate HTML content
            $htmlContent = $this->generateTeamPaymentRequestHTML([
                'team' => $team,
                'teamLeader' => $teamLeader,
                'teamWallet' => $teamWallet,
                'amount' => $request->amount,
                'notes' => $request->notes,
                'user' => auth()->user(),
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
            ]);

            // Log the request automatically for PDF generation
            TeamWalletPaymentRequestLog::create([
                'team_wallet_id' => $request->team_wallet_id,
                'user_id' => auth()->id(),
                'team_id' => $team->id,
                'team_leader_id' => $teamLeader->id,
                'amount' => $request->amount,
                'notes' => $request->notes,
                'ip_address' => IpHelper::getClientIp(),
                'printed_at' => now(),
            ]);

            return response()->json([
                'status' => 1,
                'html' => $htmlContent,
                'success' => 'تم إنشاء مستند طلب السحب النقدي بنجاح'
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 2,
                'error' => 'خطأ في إنشاء المستند: ' . $ex->getMessage()
            ]);
        }
    }

    /**
     * Generate team payment request HTML
     */
    private function generateTeamPaymentRequestHTML($data)
    {
        return view('admin.teams.wallets.payment-request-pdf', $data)->render();
    }

    /**
     * Convert number to Arabic words
     */
    public function numberToArabicWords($number)
    {
        // Same implementation as in WalletsController
        $ones = [
            '', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة',
            'عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر',
            'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'
        ];

        $tens = [
            '', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'
        ];

        $hundreds = [
            '', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'
        ];

        if ($number == 0) {
            return 'صفر';
        }
        if ($number < 0) {
            return 'سالب ' . $this->numberToArabicWords(abs($number));
        }

        $result = '';

        // Handle thousands
        if ($number >= 1000) {
            $thousands = intval($number / 1000);
            if ($thousands == 1) {
                $result .= 'ألف ';
            } elseif ($thousands == 2) {
                $result .= 'ألفان ';
            } elseif ($thousands < 11) {
                $result .= $ones[$thousands] . ' آلاف ';
            } else {
                $result .= $this->numberToArabicWords($thousands) . ' ألف ';
            }
            $number %= 1000;
        }

        // Handle hundreds
        if ($number >= 100) {
            $result .= $hundreds[intval($number / 100)] . ' ';
            $number %= 100;
        }

        // Handle tens and ones
        if ($number >= 20) {
            $result .= $tens[intval($number / 10)];
            if ($number % 10 != 0) {
                $result .= ' ' . $ones[$number % 10];
            }
        } elseif ($number > 0) {
            $result .= $ones[$number];
        }

        return trim($result);
    }

    /**
     * Check team leader for JavaScript
     */
    public function checkTeamLeader(Request $request)
    {
        $teamWalletId = $request->input('team_wallet_id');

        if (!$teamWalletId) {
            return response()->json([
                'status' => 0,
                'error' => 'معرف محفظة الفريق مطلوب'
            ]);
        }

        try {
            $teamWallet = Team_Wallet::with('team')->findOrFail($teamWalletId);
            $team = $teamWallet->team;

            // Check if team has a leader
            if (!$team->hasTeamLeader()) {
                return response()->json([
                    'status' => 0,
                    'error' => 'لا يمكن إنشاء طلب سحب نقدي - الفريق لا يملك رئيس فريق'
                ]);
            }

            $teamLeader = $team->getTeamLeaderWithBankDetails();

            if (!$teamLeader) {
                return response()->json([
                    'status' => 0,
                    'error' => 'بيانات البنك غير مكتملة لرئيس الفريق'
                ]);
            }

            return response()->json([
                'status' => 1,
                'teamLeader' => [
                    'id' => $teamLeader->id,
                    'name' => $teamLeader->name,
                    'email' => $teamLeader->email,
                    'bank_name' => $teamLeader->bank_name,
                    'account_number' => $teamLeader->account_number,
                    'iban_number' => $teamLeader->iban_number,
                ],
                'teamWallet' => [
                    'id' => $teamWallet->id,
                    'balance' => $teamWallet->balance,
                ],
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'user' => Auth::user()->name
                ]
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'status' => 2,
                'error' => 'خطأ في التحقق من بيانات الفريق: ' . $ex->getMessage()
            ]);
        }
    }

    /**
     * Get tasks for a specific team
     */
    public function getTeamTasks($teamId)
    {
        try {
            // التحقق من صلاحية المستخدم
            $user = Auth::user();
            if (!$user->checkTeam($teamId)) {
                return response()->json([
                    'status' => 0,
                    'error' => 'غير مصرح لك بالوصول لمهام هذا الفريق'
                ]);
            }

            // جلب المهام المرتبطة بالفريق
            $tasks = Task::with(['pickup', 'delivery'])
                ->where('team_id', $teamId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'text' => "مهمة #{$task->id} - " . ($task->pickup->address ?? 'عنوان غير محدد') . " - {$task->total_price} ريال - {$task->status}",
                        'status' => $task->status,
                        'total_price' => $task->total_price - $task->commission,
                        'pickup_address' => $task->pickup->address ?? 'عنوان غير محدد',
                        'delivery_address' => $task->delivery->address ?? 'عنوان غير محدد'
                    ];
                });

            return response()->json([
                'status' => 1,
                'tasks' => $tasks
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'error' => 'حدث خطأ أثناء جلب المهام: ' . $e->getMessage()
            ]);
        }
    }
}
