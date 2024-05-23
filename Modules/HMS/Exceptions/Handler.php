<?php

namespace App\Exceptions;

use Modules\HMS\App\Utilities\Helper;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException && $request->expectsJson()) {
            return Helper::errorResponse('Module not found', 404);
        }

        if ($exception instanceof AuthorizationException && $request->expectsJson()) {
            // return response()->json(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
            return Helper::errorResponse('Unauthorized', 401);
        }
        if ($exception instanceof AuthenticationException && $request->expectsJson()) {
            // return response()->json(['error' => 'Unauthorized Request'], JsonResponse::HTTP_UNAUTHORIZED);
            return Helper::errorResponse('Unauthorized Request', JsonResponse::HTTP_UNAUTHORIZED);
        }

        if ($request->expectsJson()) {
            return $this->handleApiException($exception);
            // return Helper::errorResponse('Internal Server Error', 500);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions and return JSON response.
     *
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException(Throwable $exception)
    {
        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR; // Default status code for unhandled exceptions
        $message = 'Internal Server Error'; // Default error message

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        }
        // return Helper::errorResponse($message,  $statusCode );
        return response()->json(['error' => $message], $statusCode);
    }
}
