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
        if ($request->isMethod('POST') && $request->hasHeader('X-HTTP-Method-Override')) {
            $request->setMethod($request->header('X-HTTP-Method-Override'));
        }
        return $next($request);
    }
}
