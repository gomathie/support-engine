<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->forwardContainerEnvironmentToServeCommand();
    }

    /**
     * `artisan serve` spawns `php -S` as a child process and passes it only a
     * whitelist of environment variables — APP_ENV and a handful of others.
     * Everything else is dropped, so the child re-reads .env from disk.
     *
     * In Docker that is the wrong answer: docker-compose sets DB_HOST=db as a
     * real environment variable, but the bind-mounted .env says 127.0.0.1
     * (correct for running on the host), and the child server believes the
     * file. The result is a connection-refused 500 on every page while
     * `artisan migrate` from the same container works fine.
     *
     * Caching the config papers over it, but breaks again the moment anyone
     * runs config:clear. Adding the variables to the whitelist fixes it
     * properly, and is a no-op outside the serve command.
     */
    private function forwardContainerEnvironmentToServeCommand(): void
    {
        if (! class_exists(ServeCommand::class)) {
            return;
        }

        ServeCommand::$passthroughVariables = array_values(array_unique([
            ...ServeCommand::$passthroughVariables,
            'APP_KEY',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'SESSION_DRIVER',
            'CACHE_STORE',
            'QUEUE_CONNECTION',
            'MAIL_MAILER',
            'FILESYSTEM_DISK',
            'PRIVATE_FILESYSTEM_DRIVER',
        ]));
    }
}
