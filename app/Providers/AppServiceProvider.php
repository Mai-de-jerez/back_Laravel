<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.frontend_url') 
                . '/auth/restablecer-password?token=' . $token 
                . '&email=' . urlencode($user->email);
        });

        // imprimir querys de la db temporalmente
       
        DB::listen(function ($query) {
            $bindings = $query->bindings;
            $sql = preg_replace_callback('/\?/', function () use (&$bindings) {
                $value = array_shift($bindings);
                return is_numeric($value) ? $value : "'{$value}'";
            }, $query->sql);

            Log::info("SQL: {$sql} [{$query->time}ms]");
            });
    }
}
