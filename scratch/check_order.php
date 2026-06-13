<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Outlet;

$latestOrder = Order::latest()->first();

if (!$latestOrder) {
    echo "No orders found in database.\n";
    exit;
}

echo "ORDER DETAIL:\n";
echo "Order ID: {$latestOrder->id}\n";
echo "Order Number: {$latestOrder->order_number}\n";
echo "Outlet ID: {$latestOrder->outlet_id}\n";

$outlet = $latestOrder->outlet;
if ($outlet) {
    echo "Outlet Name: {$outlet->name}\n";
    $printerConfig = $outlet->printerConfig;
    if ($printerConfig) {
        echo "Printer Config ID: {$printerConfig->id}\n";
        echo "Metadata: " . json_encode($printerConfig->metadata) . "\n";
    } else {
        echo "Printer Config: NULL (No printer config for this outlet!)\n";
    }
} else {
    echo "Outlet is NULL\n";
}
