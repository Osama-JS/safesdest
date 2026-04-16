<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Route Pricing Table
        Schema::create('company_route_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('company_warehouses')->onDelete('cascade');
            $table->foreignId('destination_province_id')->constrained('company_provinces')->onDelete('cascade');
            $table->decimal('default_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['warehouse_id', 'destination_province_id'], 'warehouse_dest_unique');
        });

        // 2. Pivot: Route Pricing per Vehicle Size (Mirrors pricing_vehicle)
        Schema::create('company_route_pricing_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_pricing_id')->constrained('company_route_pricing')->onDelete('cascade');
            $table->foreignId('vehicle_size_id')->constrained('vehicle_sizes')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['route_pricing_id', 'vehicle_size_id'], 'route_vehicle_unique');
        });

        // 3. Pivot: End Client Pricing per Vehicle Size (The highest priority)
        Schema::create('company_client_pricing_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('company_warehouses')->onDelete('cascade');
            $table->foreignId('end_client_id')->constrained('company_end_clients')->onDelete('cascade');
            $table->foreignId('vehicle_size_id')->constrained('vehicle_sizes')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['warehouse_id', 'end_client_id', 'vehicle_size_id'], 'warehouse_client_vehicle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_client_pricing_vehicles');
        Schema::dropIfExists('company_route_pricing_vehicles');
        Schema::dropIfExists('company_route_pricing');
    }
};
