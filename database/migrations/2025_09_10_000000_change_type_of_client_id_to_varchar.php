<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * As of the migration to passport v13 we change the default type to uuid.
 * But as old client ids are numeric, the column type is changed to varchar (instead of uuid).
 */
return new class extends Migration
{
    public function up(): void {
        //first temporarily drop foreign key constraints
        Schema::table('statuses', function(Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        Schema::table('webhook_creation_requests', function(Blueprint $table) {
            $table->dropForeign(['oauth_client_id']);
        });
        Schema::table('webhooks', function(Blueprint $table) {
            $table->dropForeign(['oauth_client_id']);
        });

        // then change the column type
        Schema::table('oauth_clients', function(Blueprint $table) {
            $table->string('id', 36)->change();
        });
        Schema::table('statuses', function(Blueprint $table) {
            $table->string('client_id', 36)->nullable()->change();
        });
        Schema::table('webhook_creation_requests', function(Blueprint $table) {
            $table->string('oauth_client_id', 36)->nullable()->change();
        });
        Schema::table('webhooks', function(Blueprint $table) {
            $table->string('oauth_client_id', 36)->nullable()->change();
        });

        // and finally re-add the foreign key constraints
        Schema::table('statuses', function(Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('set null');
        });
        Schema::table('webhook_creation_requests', function(Blueprint $table) {
            $table->foreign('oauth_client_id')->references('id')->on('oauth_clients')->onDelete('set null');
        });
        Schema::table('webhooks', function(Blueprint $table) {
            $table->foreign('oauth_client_id')->references('id')->on('oauth_clients')->onDelete('set null');
        });
    }

    public function down(): void {
        // first temporarily drop foreign key constraints
        Schema::table('statuses', function(Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        Schema::table('webhook_creation_requests', function(Blueprint $table) {
            $table->dropForeign(['oauth_client_id']);
        });
        Schema::table('webhooks', function(Blueprint $table) {
            $table->dropForeign(['oauth_client_id']);
        });

        // then change the column type back to integer
        Schema::table('oauth_clients', function(Blueprint $table) {
            $table->unsignedBigInteger('id')->change();
        });
        Schema::table('statuses', function(Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->change();
        });
        Schema::table('webhook_creation_requests', function(Blueprint $table) {
            $table->unsignedBigInteger('oauth_client_id')->nullable()->change();
        });
        Schema::table('webhooks', function(Blueprint $table) {
            $table->unsignedBigInteger('oauth_client_id')->nullable()->change();
        });

        // and finally re-add the foreign key constraints
        Schema::table('statuses', function(Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('set null');
        });
        Schema::table('webhook_creation_requests', function(Blueprint $table) {
            $table->foreign('oauth_client_id')->references('id')->on('oauth_clients')->onDelete('set null');
        });
        Schema::table('webhooks', function(Blueprint $table) {
            $table->foreign('oauth_client_id')->references('id')->on('oauth_clients')->onDelete('set null');
        });
    }
};
