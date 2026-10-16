<?php
$files = [
    __DIR__ . '/app/Http/Controllers/Api/Cashier/CashierDashboardController.php',
    __DIR__ . '/app/Http/Controllers/Api/Cashier/CashierPaymentController.php',
    __DIR__ . '/app/Http/Controllers/Api/Cashier/CashierReportController.php',
];

foreach ($files as $filePath) {
    if (!file_exists($filePath)) continue;
    $content = file_get_contents($filePath);

    // Remove getHotelId method
    $content = preg_replace('/protected function getHotelId\(\): \?string\s*\{.*?\n    \}/s', '', $content);

    // Remove $hotelId = $this->getHotelId(); or $hotelId = ...
    $content = preg_replace('/\$hotelId\s*=\s*\$this->getHotelId\(\);/m', '', $content);

    // Remove ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
    $content = preg_replace('/->when\(\$hotelId,\s*fn\(\$q\)\s*=>\s*\$q->where\([\'"](?:hotel_id|orders\.hotel_id)[\'"],\s*\$hotelId\)\)/', '', $content);
    $content = preg_replace('/->when\(\$hotelId,\s*function\(\$q\)\s*use\s*\(\$hotelId\)\s*\{.*?\}\)/s', '', $content);

    // Remove Order::when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
    $content = str_replace("Order::when(\$hotelId, fn(\$q) => \$q->where('hotel_id', \$hotelId))", "Order::query()", $content);
    $content = str_replace("Payment::when(\$hotelId, fn(\$q) => \$q->where('hotel_id', \$hotelId))", "Payment::query()", $content);

    // In CashierDashboardController lines 519: 'hotel_id' => $order->hotel_id
    $content = preg_replace('/[\'"]hotel_id[\'"]\s*=>\s*\$order->hotel_id,\s*/', '', $content);

    file_put_contents($filePath, $content);
    echo "Refactored: " . basename($filePath) . "\n";
}
