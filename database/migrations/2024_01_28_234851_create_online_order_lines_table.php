<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('online_order_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('online_order_id')->unsigned()->comment('id from online_orders')->nullable();
            $table->integer('product_id')->unsigned()->comment('id from products');
            $table->integer('variation_id')->unsigned()->comment('id from variations');
            $table->decimal('quantity', 22, 4);
            $table->decimal('price', 22, 4)->default(0);
            $table->decimal('total_discount_amount', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->softDeletes();
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
        Schema::dropIfExists('online_order_lines');
    }
};
