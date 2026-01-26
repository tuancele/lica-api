<?php

require 'vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$u = App\User::where('email', 'tuancele@gmail.com')->first();
if (! $u) {
    echo "User not found\n";
    exit(1);
}
$u->api_token = bin2hex(random_bytes(20));
$u->save();
echo $u->api_token."\n";
