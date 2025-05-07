<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('transactions', function (Blueprint $table) {
      $table->id();
      $table->morphs('payable'); // payable_id + payable_type
      $table->decimal('amount', 10, 2);
      $table->string('status')->default('pending'); // paid / failed / pending
      $table->string('type'); // wallet_topup / delivery / debt_payment
      $table->unsignedBigInteger('reference_id')->nullable(); // ID مرتبط مثل order id
      $table->text('note')->nullable(); // ملاحظات إضافية
      $table->string('checkout_id')->nullable(); // من HyperPay
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('transactions');
  }
}
