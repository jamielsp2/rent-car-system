<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Title and description for Google, per language, of vehicles and blog posts:
 * {"es": {"title": "...", "description": "..."}, "en": {...}}. Empty = generated from the name and summary.
 */
return new class extends Migration
{
    private const TABLES = ['cr_cars', 'posts'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'seo')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json('seo')->nullable());
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'seo')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('seo'));
            }
        }
    }
};
