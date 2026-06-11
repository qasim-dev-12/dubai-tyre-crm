<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = App\Models\User::where('name', 'smith 2')->orWhere('account_role', 0)->first();
if (!$user) {
    echo "No technician user found\n";
    exit(1);
}

$employee = $user->employee;
echo 'user:' . $user->id . ' name:' . $user->name . ' account_role:' . $user->account_role . PHP_EOL;
echo 'employee:' . ($employee ? $employee->id : 'null') . PHP_EOL;

$request = Request::create('/api/technician-battery-stocks', 'GET');
$request->setUserResolver(function () use ($user) { return $user; });

$controller = new App\Http\Controllers\API\TechnicianBatteryStockController();
$response = $controller->index($request);

if (method_exists($response, 'response')) {
    $res = $response->response();
    echo 'status:' . $res->status() . PHP_EOL;
    echo $res->getContent() . PHP_EOL;
} else {
    echo 'response object no response() method' . PHP_EOL;
    var_dump($response);
}
