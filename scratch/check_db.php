<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrinterConfig;
use App\Models\Outlet;

$configs = PrinterConfig::all();
echo "PRINTER CONFIGS:\n";
foreach ($configs as $c) {
    echo "Outlet ID: {$c->outlet_id}\n";
    echo "Printer Type: {$c->printer_type}\n";
    echo "Connection Type: {$c->connection_type}\n";
    echo "Metadata: " . json_encode($c->metadata) . "\n\n";
}

$outlets = Outlet::all();
echo "OUTLETS:\n";
foreach ($outlets as $o) {
    echo "Outlet ID: {$o->id}, Name: {$o->name}\n";
    echo "Settings: " . json_encode($o->settings) . "\n\n";
}
