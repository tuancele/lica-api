<?php

namespace App\Themes;
use Illuminate\Support\ServiceProvider;
use File;
use App\Themes\Website\Models\Cart;
use App\Modules\Website\Models\Website;
use App\Themes\Website\Models\Wishlist;
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
            $header = Website::select('block_0')->where('code','header')->first();
            $footer = Website::where('code','footer')->first();
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
            $view->with([
                'totalQty' => $totalQty,
                'wishlist' => $wishlist,
                'header' => json_decode($header->block_0),
                'footer' => $footer
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