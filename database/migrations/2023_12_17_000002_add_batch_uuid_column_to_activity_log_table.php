<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatchUuidColumnToActivityLogTable extends Migration
{
    public function up(): void {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function(Blueprint $table) {
            $table->char('batch_uuid', 36)->nullable()->after('properties');
        });
    }

    public function down(): void {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function(Blueprint $table) {
            $table->dropColumn('batch_uuid');
        });
    }
}
