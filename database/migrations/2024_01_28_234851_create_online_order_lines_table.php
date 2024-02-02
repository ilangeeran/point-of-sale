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
            $table->integer('purchase_line_id')->unsigned()->comment('id from purchase_lines');
            $table->decimal('quantity', 22, 4);
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
