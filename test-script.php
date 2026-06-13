<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=database/testing.sqlite');
    Artisan::call('test', ['--filter' => 'test_kasir_can_process_payment_cash']);
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
