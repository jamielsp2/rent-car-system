<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cédula (national ID) of the customer. Not the driver's license (license_number).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cr_customers') && ! Schema::hasColumn('cr_customers', 'national_id')) {
            Schema::table('cr_customers', function (Blueprint $table) {
                $table->string('national_id', 30)->nullable()->after('phone')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cr_customers', 'national_id')) {
            Schema::table('cr_customers', fn (Blueprint $table) => $table->dropColumn('national_id'));
        }
    }
};
