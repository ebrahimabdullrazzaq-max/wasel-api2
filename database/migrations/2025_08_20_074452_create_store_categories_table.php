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
        Schema::create('store_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores'); // ✅ Fixed: constrained()
            $table->string('name'); // e.g., "وجبات الغداء"
            $table->string('image')->nullable(); // Optional category image
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // ✅ Fixed: timestamps()
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_categories');
    }
};