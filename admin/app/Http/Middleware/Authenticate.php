<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Route;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        //return $request->expectsJson() ? null : route('login');

        if ($request->expectsJson()) {
            return null;
        }else{
            if(Route::is('admin.*')){
                return route('admin.login');
            }else{
                return route('login');
            }
        }
    }
}
