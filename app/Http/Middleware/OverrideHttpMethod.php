<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OverrideHttpMethod
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $log = "Middleware Hit! Method: " . $request->method() . " URL: " . $request->fullUrl() . " Override: " . $request->header('X-HTTP-Method-Override', 'None') . "\n";
        file_put_contents(storage_path('logs/debug.txt'), $log, FILE_APPEND);

        if ($request->isMethod('POST') && $request->hasHeader('X-HTTP-Method-Override')) {
            $request->setMethod($request->header('X-HTTP-Method-Override'));
            file_put_contents(storage_path('logs/debug.txt'), "Method Overridden to: " . $request->method() . "\n", FILE_APPEND);
        }
        return $next($request);
    }
}
