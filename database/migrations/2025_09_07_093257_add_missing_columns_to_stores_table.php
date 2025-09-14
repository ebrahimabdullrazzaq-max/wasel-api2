<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToStoresTable extends Migration
{
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('stores', 'is_new')) {
                $table->boolean('is_new')->default(true)->after('is_favorite');
            }
            
            if (!Schema::hasColumn('stores', 'opening_hour')) {
                $table->integer('opening_hour')->default(7)->after('delivery_time_max');
            }
            
            if (!Schema::hasColumn('stores', 'opening_minute')) {
                $table->integer('opening_minute')->default(30)->after('opening_hour');
            }
            
            if (!Schema::hasColumn('stores', 'closing_hour')) {
                $table->integer('closing_hour')->default(23)->after('opening_minute');
            }
            
            if (!Schema::hasColumn('stores', 'closing_minute')) {
                $table->integer('closing_minute')->default(0)->after('closing_hour');
            }
        });
    }

    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('stores', 'is_new')) {
                $table->dropColumn('is_new');
            }
            if (Schema::hasColumn('stores', 'opening_hour')) {
                $table->dropColumn('opening_hour');
            }
            if (Schema::hasColumn('stores', 'opening_minute')) {
                $table->dropColumn('opening_minute');
            }
            if (Schema::hasColumn('stores', 'closing_hour')) {
                $table->dropColumn('closing_hour');
            }
            if (Schema::hasColumn('stores', 'closing_minute')) {
                $table->dropColumn('closing_minute');
            }
        });
    }
}