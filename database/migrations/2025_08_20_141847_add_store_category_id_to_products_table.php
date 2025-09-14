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
        Schema::table('products', function (Blueprint $table) {
            // Add store_category_id after store_id
            $table->foreignId('store_category_id')->nullable()->after('store_id');
            
            // Create foreign key constraint
            $table->foreign('store_category_id')
                  ->references('id')->on('store_categories')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['store_category_id']);
            
            // Then drop the column
            $table->dropColumn('store_category_id');
        });
    }
};