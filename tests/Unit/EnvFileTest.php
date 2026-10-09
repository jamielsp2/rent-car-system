<?php

namespace Tests\Unit;

use App\Support\EnvFile;
use PHPUnit\Framework\TestCase;

class EnvFileTest extends TestCase
{
    public function test_values_are_updated_added_and_quoted_when_needed(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($path, "APP_NAME=Demo\nDB_PASSWORD=vieja\n");

        EnvFile::set(['DB_PASSWORD' => 'n#ueva $clave', 'DB_HOST' => 'localhost'], $path);

        $this->assertSame("APP_NAME=Demo\nDB_PASSWORD=\"n#ueva \\\$clave\"\nDB_HOST=localhost\n", file_get_contents($path));
        unlink($path);
    }
}
