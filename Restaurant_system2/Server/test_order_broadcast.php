<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing OrderCreated broadcast...\n";
$order = App\Models\Order::latest()->first();
if ($order) {
    echo "Order found: {$order->order_number}\n";
    try {
        App\Events\OrderCreated::dispatch($order);
        echo "? OrderCreated event dispatched successfully!\n";
        echo "Check Pusher Debug Console for event on kitchen.orders.update channel\n";
    } catch (\Throwable $e) {
        echo "? Error: {$e->getMessage()}\n";
        echo "Stack trace: {$e->getTraceAsString()}\n";
    }
} else {
    echo "No orders found\n";
}
