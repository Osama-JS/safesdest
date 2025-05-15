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
      $table->string('payment_type'); // wallet_topup / delivery / debt_payment
      $table->unsignedBigInteger('reference_id')->nullable(); // ID مرتبط مثل order id
      $table->text('note')->nullable(); // ملاحظات إضافية
      $table->string('checkout_id')->nullable(); // من HyperPay
      $table->string('receipt_image')->nullable(); // مثل credit_card / apple_pay
      $table->string('receipt_number')->nullable();
      $table->unsignedBigInteger('user_check')->nullable();
      $table->string('user_ip')->nullable();
      $table->timestamp('checkout_at')->nullable(); // تاريخ الدفع
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
