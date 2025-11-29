<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Update sales_invoice table
        Schema::table('sales_invoice', function (Blueprint $table) {
            // Add new columns
            $table->string('invoice_number')->unique()->after('id');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('pending')->after('invoice_number');
            $table->string('payment_method')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_method');

            // Financials
            $table->decimal('total_amount', 10, 2)->default(0)->after('paid_at'); // Sum of items
            $table->decimal('tax_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('final_total', 10, 2)->default(0)->after('delivery_fee');

            $table->text('notes')->nullable()->after('final_total');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');

            // Drop old columns if they exist (to move to details)
            // We use safe drop in case they don't exist or we want to keep data temporarily,
            // but for this refactor we assume we are restructuring.
            // However, to be safe with existing data, let's make them nullable first or just ignore them for now.
            // Ideally we should drop them, but let's keep them nullable for backward compatibility if needed,
            // or drop them if we are sure. Let's drop them to enforce new structure.
            $table->dropColumn(['quantity', 'unit_price', 'total_price', 'product_id', 'vehicle_size_id']);
        });

        // Create sales_invoice_details table
        Schema::create('sales_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('product_id')->nullable(); // Nullable in case product is deleted
            $table->string('product_name'); // Snapshot
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();

            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoice')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });

        // Update tasks table
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_invoice_id')->nullable()->after('id');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoice')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['sales_invoice_id']);
            $table->dropColumn('sales_invoice_id');
        });

        Schema::dropIfExists('sales_invoice_details');

        Schema::table('sales_invoice', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'status', 'payment_method', 'paid_at', 'total_amount', 'tax_amount', 'delivery_fee', 'final_total', 'notes', 'created_by']);
            // Re-add dropped columns (simplified)
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('vehicle_size_id')->nullable();
        });
    }
};
