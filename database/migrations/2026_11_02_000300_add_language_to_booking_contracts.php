<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Language the rental contract is written and signed in (es or en).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cr_booking_contracts') && ! Schema::hasColumn('cr_booking_contracts', 'language')) {
            Schema::table('cr_booking_contracts', function (Blueprint $table) {
                $table->string('language', 5)->default('es')->after('booking_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cr_booking_contracts', 'language')) {
            Schema::table('cr_booking_contracts', fn (Blueprint $table) => $table->dropColumn('language'));
        }
    }
};
