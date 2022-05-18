<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->bigInteger('member_id')->unsigned()->nullable();
            $table->unsignedBigInteger('item_buy_total');
            $table->unsignedBigInteger('item_total');
            $table->unsignedBigInteger('service_total');
            $table->unsignedBigInteger('total');
            $table->unsignedDouble('discount');
            $table->unsignedBigInteger('final_total');
            $table->unsignedBigInteger('paid');
            $table->bigInteger('credit');
            $table->string('customer_name')->default('-');
            $table->string('customer_phone_no')->default('-');
            $table->string('payment_method');
            $table->bigInteger('shop_id')->unsigned();
            $table->timestamps();
            $table->foreign('member_id')
                ->references('id')->on('members')
                ->onDelete('cascade');
            $table->foreign('shop_id')
                ->references('id')->on('shops')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
