<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aliados: users who lend their cars to the rent-a-car. Their cars point to them
 * through cr_cars.owner_id, and they see only those cars in their own panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_ally')->default(false)->after('role_id')->index();
        });

        Schema::table('cr_cars', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('vendor_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cr_cars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_ally']);
            $table->dropColumn('is_ally');
        });
    }
};
