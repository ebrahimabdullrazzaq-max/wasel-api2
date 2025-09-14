<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // ✅ Prevent redirecting to login for APIs
        if (! $request->expectsJson()) {
            return null;
        }
    }
}
