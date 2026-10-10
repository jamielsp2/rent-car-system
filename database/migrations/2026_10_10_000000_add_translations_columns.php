<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Texts in other languages: the existing columns stay the Spanish original and the
 * `translations` JSON holds the rest, e.g. {"en": {"name": "...", "description": "..."}}.
 */
return new class extends Migration
{
    private const TABLES = [
        'cr_cars', 'posts', 'faqs', 'cr_services', 'testimonials', 'cr_taxes',
        'cr_car_makes', 'cr_car_types', 'cr_car_fuels', 'cr_car_transmissions', 'cr_car_colors',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'translations')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json('translations')->nullable());
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'translations')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('translations'));
            }
        }
    }
};
