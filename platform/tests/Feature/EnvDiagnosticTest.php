<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnvDiagnosticTest extends TestCase
{
    public function test_dump_environment(): void
    {
        fwrite(STDERR, "\n--- environment as the test process sees it ---\n");
        fwrite(STDERR, 'app.env config      : '.config('app.env')."\n");
        fwrite(STDERR, 'app()->environment(): '.app()->environment()."\n");
        fwrite(STDERR, 'runningUnitTests()  : '.var_export(app()->runningUnitTests(), true)."\n");
        fwrite(STDERR, 'queue.default       : '.config('queue.default')."\n");
        fwrite(STDERR, 'db host             : '.config('database.connections.pgsql.host')."\n");
        fwrite(STDERR, 'db name             : '.config('database.connections.pgsql.database')."\n");
        fwrite(STDERR, 'getenv(APP_ENV)     : '.var_export(getenv('APP_ENV'), true)."\n");
        fwrite(STDERR, '$_ENV[APP_ENV]      : '.var_export($_ENV['APP_ENV'] ?? null, true)."\n");
        fwrite(STDERR, 'getenv(QUEUE_CONN)  : '.var_export(getenv('QUEUE_CONNECTION'), true)."\n");
        fwrite(STDERR, "-----------------------------------------------\n");

        $this->assertTrue(true);
    }
}
