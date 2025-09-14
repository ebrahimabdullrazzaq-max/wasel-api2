<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('ratings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->unsignedBigInteger('customer_id');
        $table->unsignedBigInteger('store_id');
        $table->tinyInteger('rating'); // 1 to 5 stars
        $table->text('review')->nullable();
        $table->timestamps();

        // Foreign keys
        $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');

        // One customer can rate an order only once
        $table->unique('order_id'); // One rating per order
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
