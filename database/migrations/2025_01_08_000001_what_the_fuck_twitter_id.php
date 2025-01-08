<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('social_login_profiles', function(Blueprint $table) {
            $table->string('twitter_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('social_login_profiles', function(Blueprint $table) {
            $table->string('twitter_id')->nullable(false)->change();
        });
    }
};
