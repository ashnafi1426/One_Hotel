<?php
/**
 * Deep diagnostic test for real-time Kitchen Dashboard setup
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  KITCHEN DASHBOARD REAL-TIME DIAGNOSTIC TEST                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// TEST 1: Pusher Configuration
echo "TEST 1: Pusher Configuration\n";
echo "─────────────────────────────\n";
$broadcaster = config('broadcasting.default');
$pusher = config('broadcasting.connections.pusher');

echo "  Broadcaster: " . ($broadcaster === 'pusher' ? '✅ ' : '❌ ') . $broadcaster . "\n";
echo "  Pusher Key: " . ($pusher['key'] ? '✅ ' : '❌ ') . substr($pusher['key'], 0, 10) . "...\n";
$cluster = $pusher['options']['cluster'] ?? null;
echo "  Pusher Cluster: " . ($cluster ? '✅ ' : '❌ ') . ($cluster ?: 'MISSING') . "\n";
echo "  Pusher Secret: " . (!empty($pusher['secret']) ? '✅ Set' : '❌ Empty') . "\n";
echo "\n";

// TEST 2: Event Implementation
echo "TEST 2: Event Classes\n";
echo "─────────────────────────────\n";

try {
    // Just check if classes exist and are properly configured
    $orderCreatedClass = new ReflectionClass('App\Events\OrderCreated');
    $orderUpdatedClass = new ReflectionClass('App\Events\OrderStatusUpdated');
    
    $implements1 = $orderCreatedClass->implementsInterface(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class);
    $implements2 = $orderUpdatedClass->implementsInterface(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class);
    
    echo "  OrderCreated implements ShouldBroadcastNow: " . ($implements1 ? '✅ Yes' : '❌ No') . "\n";
    echo "  OrderStatusUpdated implements ShouldBroadcastNow: " . ($implements2 ? '✅ Yes' : '❌ No') . "\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// TEST 3: Channel Authorization
echo "TEST 3: Channel Authorization\n";
echo "─────────────────────────────\n";

$channelsFile = file_get_contents('routes/channels.php');
$hasPrivateKitchen = strpos($channelsFile, "private-kitchen.orders.update") !== false;
$hasAuthorizeFunc = strpos($channelsFile, "authorizeKitchenChannel") !== false;

echo "  Channel definition private-kitchen.orders.update: " . ($hasPrivateKitchen ? '✅ Found' : '❌ Missing') . "\n";
echo "  Authorization function: " . ($hasAuthorizeFunc ? '✅ Found' : '❌ Missing') . "\n";

// Check if authorizeKitchenChannel checks roles
if ($hasAuthorizeFunc) {
    $hasRoleCheck = strpos($channelsFile, "'chef'") !== false && strpos($channelsFile, "'manager'") !== false;
    echo "  Role-based checks: " . ($hasRoleCheck ? '✅ Found' : '❌ Missing') . "\n";
}
echo "\n";

// TEST 4: API Route
echo "TEST 4: Broadcasting Auth Route\n";
echo "─────────────────────────────\n";

$apiRoutesFile = file_get_contents('routes/api.php');
$hasAuthRoute = strpos($apiRoutesFile, "/broadcasting/auth") !== false;
$hasController = strpos($apiRoutesFile, "BroadcastAuthController") !== false;

echo "  Route /broadcasting/auth: " . ($hasAuthRoute ? '✅ Found' : '❌ Missing') . "\n";
echo "  Uses BroadcastAuthController: " . ($hasController ? '✅ Yes' : '❌ No') . "\n";

// Extract the actual route definition
if (preg_match('/Route::post\([\'"]\/broadcasting\/auth[\'"][^)]*BroadcastAuthController/', $apiRoutesFile, $matches)) {
    echo "  Route definition: ✅ " . substr($matches[0], 0, 50) . "...\n";
}
echo "\n";

// TEST 5: Controller Implementation
echo "TEST 5: BroadcastAuthController\n";
echo "─────────────────────────────\n";

try {
    $hasAuthenticate = method_exists(\App\Http\Controllers\Api\BroadcastAuthController::class, 'authenticate');
    echo "  authenticate() method: " . ($hasAuthenticate ? '✅ Found' : '❌ Missing') . "\n";
    
    $hasAuthorizeKitchen = method_exists(\App\Http\Controllers\Api\BroadcastAuthController::class, 'authorizeKitchenChannel');
    echo "  authorizeKitchenChannel() method: " . ($hasAuthorizeKitchen ? '✅ Found' : '❌ Missing') . "\n";
    
    $hasGenerateAuth = method_exists(\App\Http\Controllers\Api\BroadcastAuthController::class, 'generateChannelAuth');
    echo "  generateChannelAuth() method: " . ($hasGenerateAuth ? '✅ Found' : '❌ Missing') . "\n";
    
    // Test generateChannelAuth with mock data
    $controller = new \App\Http\Controllers\Api\BroadcastAuthController();
    $reflMethod = new ReflectionMethod($controller, 'generateChannelAuth');
    $reflMethod->setAccessible(true);
    
    $testAuth = $reflMethod->invoke($controller, 'private-kitchen.orders.update', 'test-socket-123');
    echo "  Sample auth generation: ✅ " . substr($testAuth, 0, 20) . "...\n";
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// TEST 6: Environment Variables
echo "TEST 6: Environment Variables\n";
echo "─────────────────────────────\n";

echo "  APP_ENV: " . (env('APP_ENV') === 'production' ? '✅ ' : '⚠️  ') . env('APP_ENV') . "\n";
echo "  BROADCAST_CONNECTION: " . (env('BROADCAST_CONNECTION') === 'pusher' ? '✅ ' : '❌ ') . env('BROADCAST_CONNECTION') . "\n";
echo "  PUSHER_APP_ID: " . (env('PUSHER_APP_ID') ? '✅ Set' : '❌ Missing') . "\n";
echo "  PUSHER_APP_KEY: " . (env('PUSHER_APP_KEY') ? '✅ Set' : '❌ Missing') . "\n";
echo "  PUSHER_APP_CLUSTER: " . (env('PUSHER_APP_CLUSTER') ? '✅ ' . env('PUSHER_APP_CLUSTER') : '❌ Missing') . "\n";
echo "\n";

// TEST 7: Frontend Configuration Check
echo "TEST 7: Frontend Configuration (from .env files)\n";
echo "─────────────────────────────\n";

$clientEnvFile = '../Client/.env';
if (file_exists($clientEnvFile)) {
    $content = file_get_contents($clientEnvFile);
    $hasApiUrl = preg_match('/VITE_API_BASE_URL=/', $content);
    $hasPusherKey = preg_match('/VITE_PUSHER_APP_KEY=/', $content);
    $hasPusherCluster = preg_match('/VITE_PUSHER_APP_CLUSTER=/', $content);
    
    echo "  VITE_API_BASE_URL: " . ($hasApiUrl ? '✅ Configured' : '❌ Missing') . "\n";
    echo "  VITE_PUSHER_APP_KEY: " . ($hasPusherKey ? '✅ Configured' : '❌ Missing') . "\n";
    echo "  VITE_PUSHER_APP_CLUSTER: " . ($hasPusherCluster ? '✅ Configured' : '❌ Missing') . "\n";
    
    // Extract values
    if (preg_match('/VITE_API_BASE_URL=(.+)/', $content, $m)) {
        echo "  API Base URL: " . trim($m[1]) . "\n";
    }
} else {
    echo "  ⚠️  Client .env not found at $clientEnvFile\n";
}
echo "\n";

// TEST 8: Echo Plugin Configuration
echo "TEST 8: Echo Plugin (echo.ts)\n";
echo "─────────────────────────────\n";

$echoFile = '../Client/src/plugins/echo.ts';
if (file_exists($echoFile)) {
    $content = file_get_contents($echoFile);
    
    $hasAuthEndpoint = strpos($content, 'authEndpoint') !== false;
    $hasBackendUrl = strpos($content, 'VITE_API_BASE_URL') !== false;
    $hasAuthHeaders = strpos($content, 'auth:') !== false && strpos($content, 'headers:') !== false;
    $hasNoCustomAuthorizer = strpos($content, 'authorizer:') === false;
    
    echo "  Has authEndpoint config: " . ($hasAuthEndpoint ? '✅ Yes' : '❌ No') . "\n";
    echo "  Uses VITE_API_BASE_URL: " . ($hasBackendUrl ? '✅ Yes' : '❌ No') . "\n";
    echo "  Uses auth headers: " . ($hasAuthHeaders ? '✅ Yes' : '❌ No') . "\n";
    echo "  Removed custom authorizer: " . ($hasNoCustomAuthorizer ? '✅ Yes' : '❌ No') . "\n";
    
    // Extract auth endpoint
    if (preg_match('/authEndpoint:\s*[`\'"]([^`\'"]+)[`\'"]/', $content, $m)) {
        echo "  Auth endpoint: " . $m[1] . "\n";
    }
} else {
    echo "  ⚠️  echo.ts not found at $echoFile\n";
}
echo "\n";

// TEST 9: Kitchen Store Subscription
echo "TEST 9: Kitchen Store (kitchenStore.ts)\n";
echo "─────────────────────────────\n";

$kitchenStoreFile = '../Client/src/stores/kitchenStore.ts';
if (file_exists($kitchenStoreFile)) {
    $content = file_get_contents($kitchenStoreFile);
    
    $hasSubscribe = strpos($content, 'subscribeToUpdates') !== false;
    $callsEchoService = strpos($content, 'subscribeToKitchenOrderUpdates') !== false;
    
    echo "  Has subscribeToUpdates function: " . ($hasSubscribe ? '✅ Yes' : '❌ No') . "\n";
    echo "  Calls echoService.subscribeToKitchenOrderUpdates: " . ($callsEchoService ? '✅ Yes' : '❌ No') . "\n";
} else {
    echo "  ⚠️  kitchenStore.ts not found\n";
}
echo "\n";

// TEST 10: Echo Service Subscription
echo "TEST 10: Echo Service (echoService.ts)\n";
echo "─────────────────────────────\n";

$echoServiceFile = '../Client/src/services/echoService.ts';
if (file_exists($echoServiceFile)) {
    $content = file_get_contents($echoServiceFile);
    
    $hasFunction = strpos($content, 'subscribeToKitchenOrderUpdates') !== false;
    $hasPrivateChannel = strpos($content, "private('private-kitchen.orders.update')") !== false;
    $listenStatusChanged = strpos($content, "'.order.status.changed'") !== false;
    $listenCreated = strpos($content, "'.order.created'") !== false;
    
    echo "  Has subscribeToKitchenOrderUpdates: " . ($hasFunction ? '✅ Yes' : '❌ No') . "\n";
    echo "  Subscribes to private-kitchen.orders.update: " . ($hasPrivateChannel ? '✅ Yes' : '❌ No') . "\n";
    echo "  Listens for .order.status.changed: " . ($listenStatusChanged ? '✅ Yes' : '❌ No') . "\n";
    echo "  Listens for .order.created: " . ($listenCreated ? '✅ Yes' : '❌ No') . "\n";
} else {
    echo "  ⚠️  echoService.ts not found\n";
}
echo "\n";

// FINAL SUMMARY
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC COMPLETE                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ If all tests above show ✅, the system is correctly configured.\n";
echo "❌ Any ❌ indicates a configuration issue that needs fixing.\n";
echo "\n";
