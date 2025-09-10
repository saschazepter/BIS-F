<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Passport's oauth_personal_access_clients table has been redundant and unnecessary for several release cycles. Therefore, this release of Passport no longer interacts with this table or its corresponding model. If you wish, you may create a migration that drops this table:
 *
 * Schema::drop('oauth_personal_access_clients');
 * In addition, the passport.personal_access_client configuration value, Laravel\Passport\PersonalAccessClient model, Passport::$personalAccessClientModel property, Passport::usePersonalAccessClientModel(), Passport::personalAccessClientModel(), and Passport::personalAccessClient() methods have been removed.
 */
return new class extends Migration
{
    public function up(): void {
        Schema::drop('oauth_personal_access_clients');
    }
};
