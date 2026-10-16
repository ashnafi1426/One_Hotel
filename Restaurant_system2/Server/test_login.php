<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;

$admin = User::where('email', 'admin@hotela.com')->first();
$admin->password_hash = Hash::make('password123');
$admin->save();

$request = Request::create('/api/auth/login', 'POST', [
    'email' => 'admin@hotela.com',
    'password' => 'password123',
], [], [], ['HTTP_ACCEPT' => 'application/json']);

$response = $kernel->handle($request);
echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Response Body:\n" . $response->getContent() . "\n";

unlink(__FILE__);
