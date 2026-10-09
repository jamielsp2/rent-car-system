<?php

namespace App\Support;

use PDO;
use RuntimeException;

/**
 * Runs a .sql file statement by statement through PDO, so it works on shared
 * hosting without the mysql command-line client and without hitting the
 * server's packet limit. Statements end with ";" at the end of a line; "--"
 * comment lines are skipped.
 */
class SqlScript
{
    /**
     * @return int Number of statements executed.
     */
    public static function run(PDO $pdo, string $path): int
    {
        if (! is_file($path)) {
            throw new RuntimeException("No se encontró el archivo {$path}.");
        }

        $pdo->exec('SET NAMES utf8mb4');
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $count = 0;
        $statement = '';
        $handle = fopen($path, 'r');

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);

            if ($statement === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
                continue;
            }

            $statement .= $line;

            if (str_ends_with($trimmed, ';')) {
                $pdo->exec($statement);
                $statement = '';
                $count++;
            }
        }

        fclose($handle);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        return $count;
    }

    /**
     * Names of the tables a schema file creates, in file order.
     *
     * @return string[]
     */
    public static function tables(string $path): array
    {
        preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)? `([a-z_]+)`/', (string) file_get_contents($path), $matches);

        return $matches[1];
    }
}
