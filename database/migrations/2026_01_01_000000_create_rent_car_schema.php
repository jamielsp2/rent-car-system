<?php

use App\Support\SqlScript;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates every table of the system from database/schema/rent_car.sql.
 * CREATE TABLE IF NOT EXISTS, so it is harmless on a database that already has them.
 */
return new class extends Migration
{
    private const SCHEMA = 'schema/rent_car.sql';

    public function up(): void
    {
        SqlScript::run(DB::connection()->getPdo(), database_path(self::SCHEMA));
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse(SqlScript::tables(database_path(self::SCHEMA))) as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
