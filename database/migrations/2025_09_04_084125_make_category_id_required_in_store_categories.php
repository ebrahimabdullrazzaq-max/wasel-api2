<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix NULLs first
        DB::statement("UPDATE store_categories SET category_id = 1 WHERE category_id IS NULL");

        // Then make it NOT NULL
        Schema::table('store_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
        });
    }
};