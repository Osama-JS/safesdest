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
    Schema::create('customs_clearance', function (Blueprint $table) {
      $table->id();
      $table->string('status')->default('in_progress'); //  ['in_progress', 'assign', 'accepted', 'start', 'completed', 'canceled']
      $table->decimal('total_price', 10, 2)->default(0);
      $table->string('commission_type')->default('dynamic'); // ['dynamic', 'manual']
      $table->decimal('commission', 10, 2)->default(0);
      $table->string('payment_method')->default('cash'); // ['cash', 'credit', 'banking', 'wallet']
      $table->string('payment_status')->default('waiting'); // ['waiting', 'completed', 'pending']
      $table->string('payment_paid')->default('pending'); // ['all', 'just_commission', 'pending']
      $table->decimal('payment_pending_amount', 10, 2)->nullable();

      $table->boolean('closed')->default(0);
      $table->string('delivery_note')->nullable();
      $table->jsonb('additional_data')->nullable();
      $table->jsonb('pricing_history')->nullable();
      $table->jsonb('pricing_details')->nullable();


      $table->unsignedBigInteger('form_template_id')->nullable();
      $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('restrict');
      $table->unsignedBigInteger('clearance_agent_id')->nullable();
      $table->foreign('clearance_agent_id')->references('id')->on('customers')->onDelete('restrict');
      $table->unsignedBigInteger('customer_id')->nullable();
      $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
      $table->unsignedBigInteger('user_id')->nullable();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');

      $table->timestamp('completed_at')->nullable();
      $table->timestamp('closed_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customs_clearance');
  }
};
