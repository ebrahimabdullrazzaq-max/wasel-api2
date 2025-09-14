// database/migrations/xxxx_xx_xx_add_rating_to_orders_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->tinyInteger('rating')->nullable()->after('status');
            $table->timestamp('rated_at')->nullable()->after('rating');
            $table->boolean('is_rated')->default(false)->after('rated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rated_at', 'is_rated']);
        });
    }
};