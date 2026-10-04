<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomerInvoice;
use App\Models\Notification;
use App\Models\Notification_Customers;
use App\Services\AdminNotificationDispatcher;
use App\Jobs\SendEmailNotificationJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class CheckInvoiceDueDates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'invoices:check-due-dates
                            {--dry-run : Run in test mode without actually sending notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Check customer invoices due today or overdue and dispatch notifications to admins and customers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $today = Carbon::today()->format('Y-m-d');

        $this->info("=== Starting Invoice Due Dates Check [Date: {$today}] ===");
        if ($isDryRun) {
            $this->warn("🧪 DRY RUN MODE ENABLED: No notifications will actually be dispatched.");
        }

        try {
            // Get all unpaid invoices that are due today or overdue
            $invoices = CustomerInvoice::with(['customer', 'wallet'])
                ->whereIn('status', ['unpaid'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', $today)
                ->get();

            $this->info("Found {$invoices->count()} unpaid invoices requiring inspection.");

            $dueCount = 0;
            $overdueCount = 0;
            $skippedCount = 0;

            foreach ($invoices as $invoice) {
                $dueDate = Carbon::parse($invoice->due_date)->format('Y-m-d');
                $customer = $invoice->customer;
                $customerName = $customer ? $customer->name : 'عميل غير محدد';
                $remainingFormatted = number_format($invoice->remaining_amount, 2);

                if ($dueDate === $today) {
                    // ==========================================
                    // 1. الفاتورة تستحق اليوم (Due Today)
                    // ==========================================
                    $alreadyNotified = Notification::where('event_key', 'customer_invoice_due')
                        ->whereJsonContains('data->invoice_id', $invoice->id)
                        ->whereDate('created_at', $today)
                        ->exists();

                    if ($alreadyNotified) {
                        $this->line("⏩ Invoice #{$invoice->invoice_number} already notified today for due date. Skipping.");
                        $skippedCount++;
                        continue;
                    }

                    $this->info("🔔 Invoice #{$invoice->invoice_number} is DUE TODAY. Dispatching notifications...");

                    if (!$isDryRun) {
                        $this->notifyDueToday($invoice, $customerName, $remainingFormatted);
                    }
                    $dueCount++;

                } elseif ($dueDate < $today) {
                    // ==========================================
                    // 2. الفاتورة متأخرة عن موعدها (Overdue)
                    // ==========================================
                    $daysOverdue = Carbon::parse($today)->diffInDays(Carbon::parse($dueDate));

                    // Avoid spamming multiple times on the same day
                    $alreadyNotified = Notification::where('event_key', 'customer_invoice_overdue')
                        ->whereJsonContains('data->invoice_id', $invoice->id)
                        ->whereDate('created_at', $today)
                        ->exists();

                    if ($alreadyNotified) {
                        $this->line("⏩ Invoice #{$invoice->invoice_number} already notified today for overdue. Skipping.");
                        $skippedCount++;
                        continue;
                    }

                    $this->warn("⚠️ Invoice #{$invoice->invoice_number} is OVERDUE by {$daysOverdue} day(s). Dispatching notifications...");

                    if (!$isDryRun) {
                        $this->notifyOverdue($invoice, $customerName, $remainingFormatted, $daysOverdue, $dueDate);
                    }
                    $overdueCount++;
                }
            }

            $this->newLine();
            $this->info("=== Summary ===");
            $this->info("Due Today Processed: {$dueCount}");
            $this->info("Overdue Processed:   {$overdueCount}");
            $this->info("Skipped (Already):   {$skippedCount}");

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("Error checking invoice due dates: " . $e->getMessage());
            Log::error("CheckInvoiceDueDates Command Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Dispatch notifications for an invoice due today.
     */
    protected function notifyDueToday(CustomerInvoice $invoice, string $customerName, string $remaining): void
    {
        $title = "استحقاق فاتورة محاسبية اليوم: {$invoice->invoice_number}";
        $message = "تستحق اليوم الفاتورة المحاسبية رقم ({$invoice->invoice_number}) للعميل ({$customerName}) بإجمالي متبقي قدره {$remaining} ر.س.";
        $actionUrl = url("admin/wallets/{$invoice->wallet_id}");

        $extraData = [
            'invoice_id'       => $invoice->id,
            'invoice_number'   => $invoice->invoice_number,
            'wallet_id'        => $invoice->wallet_id,
            'customer_id'      => $invoice->customer_id,
            'remaining_amount' => $invoice->remaining_amount,
            'due_date'         => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('Y-m-d') : null,
        ];

        // 1. إشعار الإدارة والمحاسبين عبر المحرك الحديث
        AdminNotificationDispatcher::dispatch(
            'customer_invoice_due',
            $title,
            $message,
            $actionUrl,
            null,
            $extraData,
            'high'
        );

        // 2. إشعار العميل صاحب الفاتورة
        if ($invoice->customer_id) {
            $this->notifyCustomer(
                $invoice,
                "تذكير بموعد استحقاق الفاتورة رقم {$invoice->invoice_number}",
                "نود تذكيركم بحلول موعد استحقاق الفاتورة المحاسبية رقم ({$invoice->invoice_number}) اليوم. المبلغ المستحق للسداد: {$remaining} ر.س.",
                'customer_invoice_due'
            );
        }
    }

    /**
     * Dispatch notifications for an overdue invoice.
     */
    protected function notifyOverdue(CustomerInvoice $invoice, string $customerName, string $remaining, int $daysOverdue, string $dueDate): void
    {
        $title = "تأخر سداد فاتورة محاسبية: {$invoice->invoice_number}";
        $message = "تأخر سداد الفاتورة المحاسبية رقم ({$invoice->invoice_number}) للعميل ({$customerName}) منذ {$daysOverdue} يوم (تاريخ الاستحقاق: {$dueDate}). المبلغ المتبقي: {$remaining} ر.س.";
        $actionUrl = url("admin/wallets/{$invoice->wallet_id}");

        $extraData = [
            'invoice_id'       => $invoice->id,
            'invoice_number'   => $invoice->invoice_number,
            'wallet_id'        => $invoice->wallet_id,
            'customer_id'      => $invoice->customer_id,
            'days_overdue'     => $daysOverdue,
            'remaining_amount' => $invoice->remaining_amount,
            'due_date'         => $dueDate,
        ];

        // 1. إشعار الإدارة والمحاسبين عبر المحرك الحديث
        AdminNotificationDispatcher::dispatch(
            'customer_invoice_overdue',
            $title,
            $message,
            $actionUrl,
            null,
            $extraData,
            'high'
        );

        // 2. إشعار العميل صاحب الفاتورة بتأخر السداد
        if ($invoice->customer_id) {
            $this->notifyCustomer(
                $invoice,
                "تنبيه: تأخر سداد الفاتورة المحاسبية رقم {$invoice->invoice_number}",
                "نلفت انتباهكم لتجاوز الفاتورة رقم ({$invoice->invoice_number}) موعد استحقاقها المحدد بـ ({$daysOverdue} يوم). نرجو المبادرة بسداد المبلغ المتبقي وقدره {$remaining} ر.س.",
                'customer_invoice_overdue'
            );
        }
    }

    /**
     * Notify customer via In-App (Notification_Customers) and Email if available.
     */
    protected function notifyCustomer(CustomerInvoice $invoice, string $title, string $message, string $eventKey): void
    {
        try {
            $customer = $invoice->customer;
            if (!$customer) {
                return;
            }

            // In-app customer notification record
            DB::transaction(function () use ($invoice, $customer, $title, $message, $eventKey) {
                $notification = Notification::create([
                    'title'      => $title,
                    'message'    => $message,
                    'group'      => 'customers',
                    'type'       => 'all',
                    'action_url' => url("customers/customs-clearance"),
                    'icon'       => 'ti-file-invoice text-warning',
                    'event_key'  => $eventKey,
                    'data'       => [
                        'invoice_id'     => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'remaining'      => $invoice->remaining_amount,
                    ],
                ]);

                Notification_Customers::create([
                    'notification_id' => $notification->id,
                    'customer_id'     => $customer->id,
                    'status'          => false, // unread
                ]);
            });

            // Email to customer if email exists
            if (!empty($customer->email) && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                $emailData = [
                    'to'              => $customer->email,
                    'subject'         => "[Safedests] " . $title,
                    'content'         => $message,
                    'user_name'       => $customer->name,
                    'template'        => 'emails.notification',
                    'type'            => 'invoice_alert',
                    'priority'        => 'high',
                    'action_url'      => url("customers/customs-clearance"),
                    'action_text'     => 'عرض تفاصيل الفاتورة',
                    'additional_data' => [
                        'invoice_number' => $invoice->invoice_number,
                        'remaining'      => $invoice->remaining_amount,
                    ],
                ];

                dispatch(new SendEmailNotificationJob($emailData))->afterCommit();
            }
        } catch (Exception $e) {
            Log::error("Failed to notify customer for invoice #{$invoice->id}: " . $e->getMessage());
        }
    }
}
