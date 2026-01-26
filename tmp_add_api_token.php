<?php

require 'vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\DB::statement('ALTER TABLE `users` ADD `api_token` VARCHAR(80) NULL AFTER `remember_token`');
echo "done\n";
