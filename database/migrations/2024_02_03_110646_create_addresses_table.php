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
        $this->down();

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->enum('address_type', ['customer', 'order_billing', 'order_shipping']);
            $table->string('category', 20)->nullable();
            
            $table->unsignedInteger('customer_id')->nullable()->comment('null if guest checkout, id from users');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('order_id')->nullable()->comment('id from online_orders');
            $table->foreign('order_id')->references('id')->on('online_orders')->onDelete('cascade');

            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->string('company_name')->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('postcode');
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('email')->nullable();
            $table->char('phonecode', 6)->nullable();
            $table->string('phone')->nullable();

            $table->string('vat_id')->nullable();
            $table->boolean('default_address')
                ->default(false)
                ->comment('only for customer_addresses');

            $table->json('additional')->nullable();

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
        Schema::dropIfExists('addresses');
    }
};
