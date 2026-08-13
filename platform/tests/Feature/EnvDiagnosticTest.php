<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase as PlainTestCase;

/**
 * Temporary. Extends PHPUnit's own TestCase so it does not go through the
 * project TestCase (and its database guard) while diagnosing.
 */
class EnvDiagnosticTest extends PlainTestCase
{
    public function test_dump_environment(): void
    {
        fwrite(STDERR, "\n--- raw env before Laravel boots ---\n");
        foreach (['APP_ENV', 'DB_DATABASE', 'QUEUE_CONNECTION'] as $key) {
            fwrite(STDERR, sprintf(
                "%-18s getenv=%-20s \$_ENV=%-20s \$_SERVER=%s\n",
                $key,
                var_export(getenv($key), true),
                var_export($_ENV[$key] ?? null, true),
                var_export($_SERVER[$key] ?? null, true),
            ));
        }
        fwrite(STDERR, "------------------------------------\n");

        $this->assertTrue(true);
    }
}
