<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. جدول الفواتير المحاسبية للعملاء
        if (!Schema::hasTable('customer_invoices')) {
            Schema::create('customer_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 50)->unique(); // INV-YYYY-XXXX
                $table->string('accounting_reference_no', 100)->nullable(); // رقم الفاتورة في النظام المحاسبي
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
                $table->date('issue_date');
                $table->date('due_date'); // تاريخ الاستحقاق
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('remaining_amount', 12, 2)->default(0.00);
                $table->string('status', 30)->default('unpaid'); // unpaid, paid, approved, cancelled
                $table->dateTime('payment_date')->nullable();
                $table->string('attachment_file')->nullable(); // ملف أو صورة الفاتورة
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['customer_id', 'status']);
                $table->index(['wallet_id', 'due_date']);
                $table->index('accounting_reference_no');
            });
        }

        // 2. جدول تفاصيل الفاتورة وحركات الدين المربوطة
        if (!Schema::hasTable('customer_invoice_items')) {
            Schema::create('customer_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_invoice_id')->constrained('customer_invoices')->onDelete('cascade');
                $table->foreignId('wallet_transaction_id')->constrained('wallet_transactions')->onDelete('cascade');
                $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('set null');
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->dateTime('previous_maturity_time')->nullable();
                $table->timestamps();

                $table->index('customer_invoice_id');
                $table->index('wallet_transaction_id');
                $table->index('task_id');
            });
        }

        // 3. إضافة حقل customer_invoice_id في جدول wallet_transactions
        if (Schema::hasTable('wallet_transactions') && !Schema::hasColumn('wallet_transactions', 'customer_invoice_id')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->foreignId('customer_invoice_id')
                    ->nullable()
                    ->after('clearance_id')
                    ->constrained('customer_invoices')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wallet_transactions') && Schema::hasColumn('wallet_transactions', 'customer_invoice_id')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropForeign(['customer_invoice_id']);
                $table->dropColumn('customer_invoice_id');
            });
        }

        Schema::dropIfExists('customer_invoice_items');
        Schema::dropIfExists('customer_invoices');
    }
};
