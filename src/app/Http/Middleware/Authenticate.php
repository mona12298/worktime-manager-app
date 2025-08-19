<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {

        \Log::info('Redirect triggered', [
        'url' => $request->fullUrl(),
        'guard' => auth()->getDefaultDriver(),
        'admin_check' => auth('admin')->check(),
        'web_check' => auth('web')->check(),
    ]);

        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
