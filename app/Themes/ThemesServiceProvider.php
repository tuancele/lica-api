<?php

namespace App\Themes;
use Illuminate\Support\ServiceProvider;
use File;
use App\Themes\Website\Models\Cart;
use App\Modules\Website\Models\Website;
use App\Themes\Website\Models\Wishlist;
use App\Modules\FooterBlock\Models\FooterBlock;
use Illuminate\Support\Facades\Auth;
use Session;

class ThemesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $directories = array_map('basename', File::directories(__DIR__));
        foreach ($directories as $moduleName) {
            $this->registerModule($moduleName);
        }
        view()->composer('Website::layout',function($view){
            // #region agent log
            try {
                $logPath = base_path('.cursor/debug.log');
                $logDir = dirname($logPath);
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'G', 'location' => 'ThemesServiceProvider.php:21', 'message' => 'View composer called', 'data' => ['view' => 'Website::layout'], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
            } catch (\Exception $e) {}
            // #endregion
            $header = Website::select('block_0')->where('code','header')->first();
            $footer = Website::where('code','footer')->first();
            try {
                // #region agent log
                try {
                    @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'E', 'location' => 'ThemesServiceProvider.php:25', 'message' => 'Before FooterBlock query', 'data' => [], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
                } catch (\Exception $e) {}
                // #endregion
                $footerBlocks = FooterBlock::where('status', 1)->orderBy('sort','asc')->orderBy('id','desc')->get();
                // #region agent log
                try {
                    @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'E', 'location' => 'ThemesServiceProvider.php:27', 'message' => 'After FooterBlock query', 'data' => ['count' => $footerBlocks->count(), 'ids' => $footerBlocks->pluck('id')->toArray()], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
                } catch (\Exception $e) {}
                // #endregion
            } catch (\Exception $e) {
                // #region agent log
                try {
                    @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'F', 'location' => 'ThemesServiceProvider.php:29', 'message' => 'Exception caught', 'data' => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
                } catch (\Exception $e2) {}
                // #endregion
                $footerBlocks = collect([]);
            }
            $member = auth()->guard('member')->user();
            if(isset($member) && !empty($member)){
                $wishlist = Wishlist::select('id')->where('member_id',$member['id'])->get()->count();
            }else{
                $wishlist = 0;
            }
            
            if(Session('cart')){
                $oldCart  = Session::get('cart');
                $cart = new Cart($oldCart);
                $totalQty = $cart->totalQty;
            }else{
                $totalQty = 0;
            }
            // #region agent log
            try {
                @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H', 'location' => 'ThemesServiceProvider.php:43', 'message' => 'Passing data to view', 'data' => ['footerBlocks_count' => $footerBlocks->count(), 'footerBlocks_type' => get_class($footerBlocks)], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
            } catch (\Exception $e) {}
            // #endregion
            $view->with([
                'totalQty' => $totalQty,
                'wishlist' => $wishlist,
                'header' => $header ? json_decode($header->block_0) : null,
                'footer' => $footer,
                'footerBlocks' => $footerBlocks
            ]);
        });
    }
    public function register()
    {

    }
    private function registerModule($moduleName) 
    {
        $modulePath = __DIR__ . "/$moduleName/";
        if (File::exists($modulePath . "routes.php")) {
            $this->loadRoutesFrom($modulePath . "routes.php");
        }
        if (File::exists($modulePath . "Views")) {
            $this->loadViewsFrom($modulePath . "Views", $moduleName);
        }
        if (File::exists($modulePath . "Migrations")) {
            $this->loadMigrationsFrom($modulePath . "Migrations");
        }
        if (File::exists($modulePath . "Helpers")) {
            $helper_dir = File::allFiles($modulePath . "Helpers");
            foreach ($helper_dir as $key => $value) {
                $file = $value->getPathName();
                require $file;
            }
        }
        if (File::exists($modulePath . "Languages")) {
            //@lang('Shop::message.hello')
            $this->loadTranslationsFrom($modulePath . "Lang", $moduleName);
            // $this->loadJSONTranslationsFrom($modulePath . 'Lang');
        }
    }
}