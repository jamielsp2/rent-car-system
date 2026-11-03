<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photos of both sides of the customer's identity document (cédula, passport or
 * license). Stored on the private "local" disk, never on the public media disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cr_customers')) {
            return;
        }

        Schema::table('cr_customers', function (Blueprint $table) {
            if (! Schema::hasColumn('cr_customers', 'document_front_path')) {
                $table->string('document_front_path')->nullable()->after('document_type');
            }

            if (! Schema::hasColumn('cr_customers', 'document_back_path')) {
                $table->string('document_back_path')->nullable()->after('document_front_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cr_customers', function (Blueprint $table) {
            foreach (['document_back_path', 'document_front_path'] as $column) {
                if (Schema::hasColumn('cr_customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
