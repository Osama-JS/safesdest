<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Task;
use App\Models\Customs_Clearance;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use App\Models\Notification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class CustomerDashboardController extends Controller
{
    /**
     * Get dashboard overview data
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            // Get quick stats
            $stats = $this->getQuickStats($customer);
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities($customer, 5);
            
            // Get unread notifications count
            $unreadNotifications = Notification::where('user_type', 'customer')
                                              ->where('user_id', $customer->id)
                                              ->where('read_at', null)
                                              ->count();

            // Get wallet balance
            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'image' => $customer->image ? asset('storage/' . $customer->image) : null,
                        'email_verified' => !is_null($customer->email_verified_at),
                    ],
                    'stats' => $stats,
                    'wallet' => [
                        'balance' => $wallet ? $wallet->balance : 0,
                        'currency' => 'SAR', // or get from settings
                    ],
                    'recent_activities' => $recentActivities,
                    'notifications' => [
                        'unread_count' => $unreadNotifications,
                    ],
                    'quick_actions' => [
                        'create_task' => true,
                        'create_clearance' => true,
                        'view_wallet' => true,
                        'view_ads' => true,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed statistics
     */
    public function getStats(Request $request)
    {
        try {
            $customer = $request->user();
            $period = $request->get('period', '30'); // days

            $startDate = Carbon::now()->subDays($period);

            // Task statistics
            $taskStats = [
                'total' => Task::where('customer_id', $customer->id)->count(),
                'completed' => Task::where('customer_id', $customer->id)->where('status', 'completed')->count(),
                'in_progress' => Task::where('customer_id', $customer->id)
                                   ->whereIn('status', ['in_progress', 'assign', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point', 'unloading'])
                                   ->count(),
                'canceled' => Task::where('customer_id', $customer->id)->where('status', 'canceled')->count(),
                'recent' => Task::where('customer_id', $customer->id)
                              ->where('created_at', '>=', $startDate)
                              ->count(),
            ];

            // Customs clearance statistics
            $clearanceStats = [
                'total' => Customs_Clearance::where('customer_id', $customer->id)->count(),
                'completed' => Customs_Clearance::where('customer_id', $customer->id)->where('status', 'completed')->count(),
                'in_progress' => Customs_Clearance::where('customer_id', $customer->id)->where('status', 'in_progress')->count(),
                'recent' => Customs_Clearance::where('customer_id', $customer->id)
                                            ->where('created_at', '>=', $startDate)
                                            ->count(),
            ];

            // Financial statistics
            $wallet = Wallet::where('user_type', 'customer')
                           ->where('user_id', $customer->id)
                           ->first();

            $financialStats = [
                'current_balance' => $wallet ? $wallet->balance : 0,
                'total_deposits' => Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
                    $query->where('user_type', 'customer')->where('user_id', $customer->id);
                })->where('transaction_type', 'credit')->sum('amount'),
                'total_spent' => Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
                    $query->where('user_type', 'customer')->where('user_id', $customer->id);
                })->where('transaction_type', 'debit')->sum('amount'),
                'recent_transactions' => Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
                    $query->where('user_type', 'customer')->where('user_id', $customer->id);
                })->where('created_at', '>=', $startDate)->count(),
            ];

            // Monthly trends (last 6 months)
            $monthlyTrends = [];
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                
                $monthlyTrends[] = [
                    'month' => $monthStart->format('Y-m'),
                    'month_name' => $monthStart->format('M Y'),
                    'tasks' => Task::where('customer_id', $customer->id)
                                 ->whereBetween('created_at', [$monthStart, $monthEnd])
                                 ->count(),
                    'clearances' => Customs_Clearance::where('customer_id', $customer->id)
                                                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                                                    ->count(),
                    'spending' => Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
                        $query->where('user_type', 'customer')->where('user_id', $customer->id);
                    })->where('transaction_type', 'debit')
                      ->whereBetween('created_at', [$monthStart, $monthEnd])
                      ->sum('amount'),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period . ' days',
                    'tasks' => $taskStats,
                    'clearances' => $clearanceStats,
                    'financial' => $financialStats,
                    'monthly_trends' => $monthlyTrends,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(Request $request)
    {
        try {
            $customer = $request->user();
            $limit = $request->get('limit', 10);

            $activities = $this->getRecentActivities($customer, $limit);

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard notifications
     */
    public function getNotifications(Request $request)
    {
        try {
            $customer = $request->user();
            $limit = $request->get('limit', 5);

            $notifications = Notification::where('user_type', 'customer')
                                        ->where('user_id', $customer->id)
                                        ->orderBy('created_at', 'desc')
                                        ->limit($limit)
                                        ->get()
                                        ->map(function ($notification) {
                                            return [
                                                'id' => $notification->id,
                                                'title' => $notification->title,
                                                'message' => $notification->message,
                                                'type' => $notification->type,
                                                'read_at' => $notification->read_at,
                                                'created_at' => $notification->created_at,
                                                'data' => $notification->data,
                                            ];
                                        });

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quick statistics for dashboard
     */
    private function getQuickStats($customer)
    {
        return [
            'tasks' => [
                'total' => Task::where('customer_id', $customer->id)->count(),
                'active' => Task::where('customer_id', $customer->id)
                              ->whereIn('status', ['in_progress', 'assign', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point', 'unloading'])
                              ->count(),
                'completed_today' => Task::where('customer_id', $customer->id)
                                       ->where('status', 'completed')
                                       ->whereDate('updated_at', Carbon::today())
                                       ->count(),
            ],
            'clearances' => [
                'total' => Customs_Clearance::where('customer_id', $customer->id)->count(),
                'active' => Customs_Clearance::where('customer_id', $customer->id)
                                            ->where('status', 'in_progress')
                                            ->count(),
                'completed_today' => Customs_Clearance::where('customer_id', $customer->id)
                                                     ->where('status', 'completed')
                                                     ->whereDate('updated_at', Carbon::today())
                                                     ->count(),
            ],
            'ads' => [
                'active_task_ads' => Task::where('customer_id', $customer->id)
                                       ->where('status', 'advertised')
                                       ->count(),
                'active_clearance_ads' => Customs_Clearance::where('customer_id', $customer->id)
                                                          ->where('status', 'advertised')
                                                          ->count(),
            ]
        ];
    }

    /**
     * Get recent activities for customer
     */
    private function getRecentActivities($customer, $limit = 10)
    {
        $activities = collect();

        // Get recent tasks
        $recentTasks = Task::where('customer_id', $customer->id)
                          ->orderBy('updated_at', 'desc')
                          ->limit($limit)
                          ->get();

        foreach ($recentTasks as $task) {
            $activities->push([
                'id' => 'task_' . $task->id,
                'type' => 'task',
                'title' => 'Task #' . $task->id,
                'description' => 'Task status: ' . ucfirst(str_replace('_', ' ', $task->status)),
                'status' => $task->status,
                'date' => $task->updated_at,
                'data' => [
                    'task_id' => $task->id,
                    'from_location' => $task->from_location,
                    'to_location' => $task->to_location,
                ]
            ]);
        }

        // Get recent clearances
        $recentClearances = Customs_Clearance::where('customer_id', $customer->id)
                                            ->orderBy('updated_at', 'desc')
                                            ->limit($limit)
                                            ->get();

        foreach ($recentClearances as $clearance) {
            $activities->push([
                'id' => 'clearance_' . $clearance->id,
                'type' => 'clearance',
                'title' => 'Clearance #' . $clearance->id,
                'description' => 'Clearance status: ' . ucfirst(str_replace('_', ' ', $clearance->status)),
                'status' => $clearance->status,
                'date' => $clearance->updated_at,
                'data' => [
                    'clearance_id' => $clearance->id,
                ]
            ]);
        }

        // Get recent wallet transactions
        $recentTransactions = Wallet_Transaction::whereHas('wallet', function($query) use ($customer) {
            $query->where('user_type', 'customer')->where('user_id', $customer->id);
        })->orderBy('created_at', 'desc')
          ->limit($limit)
          ->get();

        foreach ($recentTransactions as $transaction) {
            $activities->push([
                'id' => 'transaction_' . $transaction->id,
                'type' => 'transaction',
                'title' => ucfirst($transaction->transaction_type) . ' Transaction',
                'description' => $transaction->description ?: ($transaction->transaction_type === 'credit' ? 'Wallet deposit' : 'Wallet withdrawal'),
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'date' => $transaction->created_at,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_type' => $transaction->transaction_type,
                ]
            ]);
        }

        // Sort by date and limit
        return $activities->sortByDesc('date')->take($limit)->values();
    }
}
