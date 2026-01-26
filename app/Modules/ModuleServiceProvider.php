<?php

declare(strict_types=1);

namespace App\Modules;

use File;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $directories = array_map('basename', File::directories(__DIR__));
        foreach ($directories as $moduleName) {
            $this->registerModule($moduleName);
        }
    }

    public function register() {}

    private function registerModule($moduleName)
    {
        $modulePath = __DIR__."/$moduleName/";
        if (File::exists($modulePath.'routes.php')) {
            $this->loadRoutesFrom($modulePath.'routes.php');
        }
        if (File::exists($modulePath.'Views')) {
            $this->loadViewsFrom($modulePath.'Views', $moduleName);
        }
        if (File::exists($modulePath.'Helpers')) {
            $helper_dir = File::allFiles($modulePath.'Helpers');
            foreach ($helper_dir as $key => $value) {
                $file = $value->getPathName();
                require $file;
            }
        }
        if (File::exists(__DIR__.'/Function.php')) {
            require __DIR__.'/Function.php';
        }
    }
}
