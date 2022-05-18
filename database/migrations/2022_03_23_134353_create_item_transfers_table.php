<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_transfers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('to_shop_id')->unsigned();
            $table->bigInteger('shop_id')->unsigned();
            $table->bigInteger('stock_id')->unsigned();
            $table->unsignedBigInteger('quantity');
            $table->timestamps();
            $table->foreign('shop_id')
                ->references('id')->on('shops')
                ->onDelete('cascade');
            $table->foreign('to_shop_id')
                ->references('id')->on('shops')
                ->onDelete('cascade');
            $table->foreign('stock_id')
                ->references('id')->on('stocks')
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
        Schema::dropIfExists('item_transfers');
    }
}
