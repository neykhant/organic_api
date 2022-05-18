<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDamageItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('damage_items', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->bigInteger('stock_id')->unsigned();
            $table->unsignedBigInteger('quantity');
            $table->bigInteger('shop_id')->unsigned();
            $table->boolean('is_sale');
            $table->timestamps();
            $table->foreign('stock_id')
                ->references('id')->on('stocks')
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
        Schema::dropIfExists('damage_items');
    }
}
