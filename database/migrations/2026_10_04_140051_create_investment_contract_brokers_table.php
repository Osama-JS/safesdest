<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('investment_contract_brokers')) {
            Schema::create('investment_contract_brokers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('investment_contract_id')
                    ->constrained('investment_contracts')
                    ->onDelete('cascade');
                $table->foreignId('broker_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->string('broker_commission_source')->default('investor_commission');
                $table->string('broker_commission_type')->default('percentage');
                $table->decimal('broker_commission_value', 10, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['investment_contract_id', 'broker_id'], 'contract_broker_idx');
            });
        }

        // ترحيل أي بيانات سابقة من جدول investment_contracts لضمان عدم ضياع أي وسيط قديم
        if (Schema::hasTable('investment_contracts') && Schema::hasColumn('investment_contracts', 'broker_id')) {
            $existingContracts = DB::table('investment_contracts')
                ->whereNotNull('broker_id')
                ->where('broker_id', '>', 0)
                ->get();

            foreach ($existingContracts as $contract) {
                $alreadyExists = DB::table('investment_contract_brokers')
                    ->where('investment_contract_id', $contract->id)
                    ->where('broker_id', $contract->broker_id)
                    ->exists();

                if (!$alreadyExists) {
                    DB::table('investment_contract_brokers')->insert([
                        'investment_contract_id'   => $contract->id,
                        'broker_id'                => $contract->broker_id,
                        'broker_commission_source' => $contract->broker_commission_source ?? 'investor_commission',
                        'broker_commission_type'   => $contract->broker_commission_type ?? 'percentage',
                        'broker_commission_value'  => $contract->broker_commission_value ?? 0.00,
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_contract_brokers');
    }
};
