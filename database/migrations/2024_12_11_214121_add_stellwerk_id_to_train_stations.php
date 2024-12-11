<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('train_stations', function(Blueprint $table) {
            $table->string('stellwerk_id')->nullable()->unique()->after('wikidata_id');
            $table->index('stellwerk_id');
        });
    }

    public function down(): void {
        Schema::table('train_stations', function(Blueprint $table) {
            $table->dropIndex('train_stations_stellwerk_id_index');
            $table->dropColumn('stellwerk_id');
        });
    }
};
