<?php

namespace App\Providers;

use App\Models\usersModel;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // \Illuminate\Support\Facades\URL::forceScheme('https');
        
        View::composer('*', function ($view) {
            if (session()->has('user_id')) {
                $user_id = session('user_id');

                $userData = usersModel::where('id', $user_id)->first();
                
                $view->with('provider_user', $userData);
            }
        });
    }
}
