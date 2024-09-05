<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\UserNotDefinedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use App\Traits\JsonResponse;

class JwtAuthMiddleware
{
    use JsonResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        try {

            if (in_array(basename($request->path()), $this->pathToExcludeFromMiddleware())) {
                return $next($request);
            }
            $user = Auth::userOrFail();
            // if( Auth::user() === null) return JsonResponse::errorResponse('User is Logged In', 401);
        } catch (JWTException $e) {
            if ($e instanceof TokenInvalidException) {
                return JsonResponse::errorResponse('Token is Invalid', 401);
            } else if ($e instanceof TokenExpiredException) {
                return JsonResponse::errorResponse('Token is Expired', 401);
            } else if ($e instanceof UserNotDefinedException) {
                return JsonResponse::errorResponse('User Does not Exists', 401);
            }
            else {
                return JsonResponse::errorResponse('Authorization Token is Invalid', 401);
            }
        }
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
