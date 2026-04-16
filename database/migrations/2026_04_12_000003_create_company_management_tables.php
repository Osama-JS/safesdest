<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Company Warehouses
        Schema::create('company_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('province_id')->constrained('company_provinces')->onDelete('cascade');
            $table->string('name', 191);
            $table->string('city', 100)->nullable();
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('contact_name', 191);
            $table->string('contact_phone', 30);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'province_id']);
        });

        // 2. Company End Clients
        Schema::create('company_end_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('province_id')->constrained('company_provinces')->onDelete('cascade');
            $table->string('name', 191);
            $table->string('phone', 30);
            $table->string('phone_2', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'phone']);
            $table->index(['company_id', 'province_id']);
        });

        // 3. Company Pricing Configs
        Schema::create('company_pricing_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('customers')->onDelete('cascade');
            $table->enum('commission_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(15.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_pricing_configs');
        Schema::dropIfExists('company_end_clients');
        Schema::dropIfExists('company_warehouses');
    }
};
