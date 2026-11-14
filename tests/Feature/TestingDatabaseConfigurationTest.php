<?php

namespace Tests\Feature;

use Tests\TestCase;

class TestingDatabaseConfigurationTest extends TestCase
{
    public function test_tests_use_an_isolated_in_memory_sqlite_database(): void
    {
        $this->assertSame('sqlite', env('DB_CONNECTION'));
        $this->assertSame(':memory:', env('DB_DATABASE'));
        $this->assertSame('bootstrap/cache/config.testing.php', env('APP_CONFIG_CACHE'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('', config('database.connections.sqlite.url'));
    }
}
