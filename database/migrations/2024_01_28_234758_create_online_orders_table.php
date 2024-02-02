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
        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->string('order_name')->nullable();
            $table->dateTime('ordered_date');
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->decimal('total_discount_amount', 22, 4)->default(0);
            $table->decimal('final_total', 22, 4)->default(0);
            $table->enum('status', ['received', 'pending', 'ordered', 'draft', 'final']);
            $table->text('additional_notes')->nullable();
            $table->integer('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
            

            //Indexing
            $table->index('business_id');
            $table->index('ordered_date');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('online_orders');
    }
};
