<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether national_id is a cédula or a passport number.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cr_customers') && ! Schema::hasColumn('cr_customers', 'document_type')) {
            Schema::table('cr_customers', function (Blueprint $table) {
                $table->string('document_type', 20)->default('cedula')->after('national_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cr_customers', 'document_type')) {
            Schema::table('cr_customers', fn (Blueprint $table) => $table->dropColumn('document_type'));
        }
    }
};
