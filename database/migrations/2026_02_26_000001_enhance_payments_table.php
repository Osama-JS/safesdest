<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Purpose of payment
            $table->string('purpose')->nullable()->after('gateway_name')
                ->comment('task_payment, clearance_payment, wallet_deposit');

            // What was paid
            $table->string('payment_paid')->nullable()->after('purpose')
                ->comment('all, just_commission');

            // Reference
            $table->unsignedBigInteger('reference_id')->nullable()->after('payment_paid');

            // Owner (could be customer or user)
            $table->string('owner_type')->nullable()->after('reference_id')
                ->comment('customer, user');
            $table->unsignedBigInteger('owner_id')->nullable()->after('owner_type');

            // Secure token for payment URL (shared with mobile)
            $table->string('payment_token', 64)->unique()->nullable()->after('owner_id');

            // Gateway details
            $table->string('gateway_code')->nullable()->after('gateway_response');
            $table->text('gateway_msg')->nullable()->after('gateway_code');
            $table->string('gateway_reference')->nullable()->after('gateway_msg');

            // Timestamps
            $table->timestamp('expires_at')->nullable()->after('gateway_reference');
            $table->timestamp('completed_at')->nullable()->after('expires_at');
            $table->timestamp('processed_at')->nullable()->after('completed_at');

            // For bank transfer
            $table->text('receipt_image')->nullable()->after('processed_at');
            $table->string('receipt_number')->nullable()->after('receipt_image');
            $table->text('description')->nullable()->after('receipt_number');

            // For cancellation
            $table->string('cancellation_reason')->nullable()->after('description');
            $table->timestamp('canceled_at')->nullable()->after('cancellation_reason');

            // Return URL (for web redirect after payment)
            $table->string('return_url')->nullable()->after('canceled_at');
        });

        // For PostgreSQL: use string columns with check constraints instead of ENUM modification
        // (Postgres ENUMs cannot be altered easily — use unconstrained string, enforce in app logic)
        DB::statement("ALTER TABLE payments ALTER COLUMN payment_method TYPE VARCHAR(50)");
        DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE VARCHAR(30)");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'purpose', 'payment_paid', 'reference_id', 'owner_type', 'owner_id',
                'payment_token', 'gateway_code', 'gateway_msg', 'gateway_reference',
                'expires_at', 'completed_at', 'processed_at',
                'receipt_image', 'receipt_number', 'description',
                'cancellation_reason', 'canceled_at', 'return_url',
            ]);
        });
    }
};
