<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalWorkspace
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true)
            && in_array($request->getHost(), ['localhost', '127.0.0.1', '[::1]', '::1'], true), 403);

        return $next($request);
    }
}
