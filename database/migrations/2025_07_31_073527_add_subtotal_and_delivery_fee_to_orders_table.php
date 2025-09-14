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
    Schema::table('orders', function ($table) {
        $table->decimal('subtotal', 10, 2)->nullable();
        $table->decimal('delivery_fee', 10, 2)->default(5.00);
    });
}


    /**
     * Reverse the migrations.
     */
 public function down()
{
    Schema::table('orders', function ($table) {
        $table->dropColumn('subtotal');
        $table->dropColumn('delivery_fee');
    });
}
};
