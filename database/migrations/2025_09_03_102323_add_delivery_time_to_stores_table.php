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
    Schema::table('stores', function (Blueprint $table) {
        $table->integer('delivery_time_min')->nullable();
        $table->integer('delivery_time_max')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
   public function down()
{
    Schema::table('stores', function (Blueprint $table) {
        $table->dropColumn(['delivery_time_min', 'delivery_time_max']);
    });
}
};
