<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set Arabic locale for admin panel - must also set session so filament-language-switch
        // (which runs later and reads from session first) doesn't overwrite with stale 'en'
        if ($request->is('admin*')) {
            app()->setLocale('ar');
            session(['locale' => 'ar']);
        }

        return $next($request);
    }
}
