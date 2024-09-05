<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CustomPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // dd($request->method());
        if (in_array(basename($request->path()), $this->pathToExcludeFromMiddleware())) {
            return $next($request);
        }
        // $route_has_permission = DB::table('route_has_permissions')->where('route', basename($request->path()))->where('method', $request->method())->first();
        // if(!$route_has_permission) throw new Exception('No permission not found for current route.');
        // if(!$request->user()->can($route_has_permission->permission)) throw new Exception('Permission not allowed.');
        return $next($request);
    }

    public function pathToExcludeFromMiddleware(): array
    {
        return [
            'login',
            'register'
        ];
    }
}
