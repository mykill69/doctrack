<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\Group;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
   public function boot()
{
    View::composer('*', function ($view) {
        try {
            $groups = Schema::hasTable('groups') ? Group::all() : collect();
            $users = Schema::hasTable('users') ? User::all() : collect();
        } catch (\Throwable $e) {
            // DB down / bad credentials / table missing:
            // share empty collections so pages still render;
            // real error stays in laravel.log
            report($e);
            $groups = collect();
            $users = collect();
        }
        $view->with(compact('groups', 'users'));
    });
}
}
