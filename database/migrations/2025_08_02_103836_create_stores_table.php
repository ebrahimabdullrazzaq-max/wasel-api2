<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('phone')->nullable();
            $table->string('image')->nullable();
            $table->time('opening_time')->default('08:00:00');
            $table->time('closing_time')->default('22:00:00');
            $table->boolean('is_open')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('total_ratings')->default(0);
            $table->integer('rating_sum')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stores');
    }
};