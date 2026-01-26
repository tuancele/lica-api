<?php

declare(strict_types=1);
namespace App\Providers;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Modules\Permission\Models\Permission;
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Product' => 'App\Policies\ProductPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        //Gate::before(function($user){
           // if($user->role_id === 1){
               // return true;
            //}
        //});
        //if(! $this->app->runningInConsole()){
           // foreach (Permission::all() as $permission) {
            //    Gate::define($permission->name, function($user) use ($permission){
                   // return $user->hasPermission($permission);
                //});
           // }
        //}
    }
}
