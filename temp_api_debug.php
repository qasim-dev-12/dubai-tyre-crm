<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

$user = User::where('name', 'smith 2')->orWhere('account_role', 0)->first();
if (!$user) {
    echo "No user found\n";
    exit(1);
}

try {
    $token = JWTAuth::fromUser($user);
    echo "TOKEN=" . $token . "\n";
} catch (Exception $e) {
    echo "TOKEN ERROR=" . $e->getMessage() . "\n";
    exit(1);
}

$url = 'http://127.0.0.1:8000/api/technician-battery-stocks';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP_STATUS=" . $info['http_code'] . "\n";
if ($err) {
    echo "CURL_ERROR=" . $err . "\n";
}
echo "RESPONSE=" . $response . "\n";
