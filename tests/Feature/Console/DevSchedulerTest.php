<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\DevCommands;
use Tests\TestCase;

class DevSchedulerTest extends TestCase
{
    public function test_composer_run_dev_menjalankan_scheduler(): void
    {
        $commands = collect(DevCommands::commands())->keyBy('name');

        $this->assertTrue($commands->has('scheduler'));
        $this->assertSame('php artisan schedule:work', $commands->get('scheduler')['command']);
    }
}
