<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add column if it doesn't exist
        if (!Schema::hasColumn('store_categories', 'category_id')) {
            Schema::table('store_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable();
            });
        }

        // Set a default global category (e.g., first one in `categories`)
        $defaultCategoryId = DB::table('categories')->value('id');
        if ($defaultCategoryId) {
            DB::statement("UPDATE store_categories SET category_id = $defaultCategoryId WHERE category_id IS NULL");
        } else {
            // If no categories exist, create one
            $defaultCategoryId = DB::table('categories')->insertGetId([
                'name' => 'General',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::statement("UPDATE store_categories SET category_id = $defaultCategoryId WHERE category_id IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }
};