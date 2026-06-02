<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        // Fix for Postgres + Neon/PgBouncer + PDO Emulation
        // When emulation is ON, Laravel's integer boolean bindings (1/0) fail in Postgres.
        // We force them to be sent as 'true'/'false' strings.
        \Illuminate\Database\Connection::resolverFor('pgsql', function ($connection, $database, $prefix, $config) {
            return new class($connection, $database, $prefix, $config) extends \Illuminate\Database\PostgresConnection
            {
                public function prepareBindings(array $bindings)
                {
                    foreach ($bindings as $key => $value) {
                        if (is_bool($value)) {
                            $bindings[$key] = $value ? 'true' : 'false';
                        }
                    }

                    return parent::prepareBindings($bindings);
                }
            };
        });
    }
}
