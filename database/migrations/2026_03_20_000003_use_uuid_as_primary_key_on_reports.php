<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        // Recreate table to swap integer PK for UUID PK (required for SQLite compatibility)
        Schema::create('reports_new', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status')->default('open')->comment('Enum ReportStatus');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('reason')->nullable()->comment('Enum ReportReason or null.');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('reporter_id')->nullable();
            $table->unsignedBigInteger('admin_notification_id')->nullable();
            $table->timestamps();

            $table->foreign('reporter_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['status']);
        });

        DB::table('reports_new')->insertUsing(
            ['id', 'status', 'subject_type', 'subject_id', 'reason', 'description', 'reporter_id', 'admin_notification_id', 'created_at', 'updated_at'],
            DB::table('reports')->select(['uuid', 'status', 'subject_type', 'subject_id', 'reason', 'description', 'reporter_id', 'admin_notification_id', 'created_at', 'updated_at']),
        );

        Schema::drop('reports');
        Schema::rename('reports_new', 'reports');
    }

    public function down(): void
    {
        // Irreversible — integer IDs from the original table cannot be restored
    }
};
