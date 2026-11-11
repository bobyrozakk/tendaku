<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DatabaseSafetyTest extends TestCase
{
    public function test_database_is_sqlite_memory(): void
    {
        $this->assertSame(
            'testing',
            app()->environment(),
            'APP_ENV bukan testing!'
        );

        $this->assertSame(
            'sqlite',
            config('database.default'),
            'Default connection bukan SQLite!'
        );

        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName(),
            'Database driver bukan SQLite!'
        );

        $this->assertSame(
            ':memory:',
            DB::connection()->getDatabaseName(),
            'Database bukan in-memory!'
        );
    }
}
