<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

class EnvDiagnosticTest extends LaravelTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function test_dump(): void
    {
        fwrite(STDERR, "\n--- after Laravel boots ---\n");
        fwrite(STDERR, 'app.env      : '.var_export(config('app.env'), true)."\n");
        fwrite(STDERR, 'app.key set  : '.var_export(! empty(config('app.key')), true)."\n");
        fwrite(STDERR, 'env(APP_KEY) : '.var_export(env('APP_KEY') ? 'present' : 'EMPTY', true)."\n");
        fwrite(STDERR, 'db database  : '.var_export(config('database.connections.pgsql.database'), true)."\n");
        fwrite(STDERR, 'queue        : '.var_export(config('queue.default'), true)."\n");
        fwrite(STDERR, 'env file used: '.var_export(app()->environmentFile(), true)."\n");
        fwrite(STDERR, '.env.testing exists: '.var_export(file_exists(base_path('.env.testing')), true)."\n");
        fwrite(STDERR, "---------------------------\n");

        $this->assertTrue(true);
    }
}
