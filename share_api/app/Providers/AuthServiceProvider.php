<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //定义gate Gates 总是接收用户实例作为第一个参数
        $permissions = \App\Permission::all();
        foreach ($permissions as $permission){
            Gate::define($permission->desc, function ($user)use($permission){
                return $user->hasPermission($permission);
            });
        }
    }
}
