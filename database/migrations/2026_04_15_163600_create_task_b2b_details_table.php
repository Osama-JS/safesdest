<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_b2b_details', function (Blueprint $table) {
            $table->id();

            // ── محاور الربط الأساسية ──
            $table->foreignId('task_id')
                  ->unique()
                  ->constrained('tasks')
                  ->onDelete('cascade');

            $table->foreignId('company_id')
                  ->constrained('customers');

            $table->foreignId('warehouse_id')
                  ->constrained('company_warehouses');

            $table->foreignId('end_client_id')
                  ->constrained('company_end_clients');

            $table->foreignId('vehicle_size_id')
                  ->constrained('vehicle_sizes');

            // ── Snapshot التسعير ──
            $table->decimal('base_price', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            // أي طبقة تسعير طُبِّقت
            $table->string('pricing_rule', 30)->nullable();
            // 'client_vehicle' | 'route_vehicle' | 'route_default'

            // ── Snapshot بيانات المستودع (Pickup) ──
            $table->string('pickup_name');
            $table->string('pickup_phone', 30);
            $table->string('pickup_address')->nullable();
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();

            // ── Snapshot بيانات العميل النهائي (Delivery) ──
            $table->string('delivery_name');
            $table->string('delivery_phone', 30)->nullable();
            $table->string('delivery_address')->nullable();
            $table->decimal('delivery_lat', 10, 7)->nullable();
            $table->decimal('delivery_lng', 10, 7)->nullable();

            $table->timestamps();

            // فهارس للأداء
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['company_id', 'end_client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_b2b_details');
    }
};
