<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Components\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use JsonException;
use Paganini\Aggregation\Exceptions\DownstreamServiceException;
use Paganini\Constants\ResponseConstant;
use Paganini\Exceptions\IllegalArgumentException;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use ValueError;

/**
 * JSON envelope for {@code api/*} requests that expect JSON.
 */
final class ApiJsonExceptionHandler
{
    /**
     * @throws RandomException
     */
    public static function render(Request $request, Throwable $exception): ?JsonResponse
    {
        if (! str_starts_with($request->path(), 'api/')) {
            return null;
        }

        $reqId = $request->header('X-Request-Id') ?: bin2hex(random_bytes(8));

        if ($exception instanceof ValidationException) {
            $message = $exception->validator->errors()->first() ?: 'Validation failed.';
            $payload = ApiResponse::error(ResponseConstant::RET_INVALID_PARAM, $message, $reqId);
            $payload['errors'] = $exception->errors();

            return response()->json($payload, 422);
        }

        if ($exception instanceof HttpException) {
            return self::httpExceptionResponse($exception, $reqId);
        }

        if ($exception instanceof FoundationAuthRequiredException) {
            return response()->json(
                ApiResponse::error(
                    (int) config('user_agg.foundation.unauthorized_code', ResponseConstant::RET_UNAUTHORIZED),
                    $exception->getMessage(),
                    $reqId
                ),
                401
            );
        }

        if ($exception instanceof DownstreamServiceException && self::isLoginRequiredMessage($exception->getMessage())) {
            return response()->json(
                ApiResponse::error(
                    (int) config('user_agg.foundation.unauthorized_code', ResponseConstant::RET_UNAUTHORIZED),
                    $exception->getMessage(),
                    $reqId
                ),
                401
            );
        }

        if ($exception instanceof DownstreamServiceException) {
            return response()->json(
                ApiResponse::error(ResponseConstant::RET_DEPENDENCY_ERROR, $exception->getMessage(), $reqId),
                502
            );
        }

        if ($exception instanceof ModelNotFoundException) {
            $message = self::modelNotFoundMessage($request);

            return response()->json(
                ApiResponse::error(ResponseConstant::RET_RESOURCE_NOT_FOUND, $message, $reqId),
                404
            );
        }

        if ($exception instanceof ValueError) {
            $message = $exception->getMessage();
            if ($message === '') {
                $message = 'Invalid parameter value.';
            }

            return response()->json(
                ApiResponse::error(ResponseConstant::RET_INVALID_PARAM, $message, $reqId),
                422
            );
        }

        if ($exception instanceof IllegalArgumentException) {
            return response()->json(
                ApiResponse::error(ResponseConstant::RET_INVALID_PARAM, $exception->getMessage(), $reqId),
                422
            );
        }

        if ($exception instanceof JsonException) {
            return response()->json(
                ApiResponse::error(ResponseConstant::RET_JSON_PARSE_ERROR, 'Invalid JSON.', $reqId),
                500
            );
        }

        if ($exception instanceof RuntimeException && $request->is('api/openapi.json', 'api/openai.json')) {
            return response()->json(
                ApiResponse::error(ResponseConstant::RET_UNKNOWN, $exception->getMessage(), $reqId),
                500
            );
        }

        if ($exception instanceof RuntimeException) {
            return response()->json(
                ApiResponse::error(ResponseConstant::RET_BUSINESS_ERROR, $exception->getMessage(), $reqId),
                422
            );
        }

        Log::error('Uncaught API exception', [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            '_req_id' => $reqId,
        ]);

        $publicMessage = config('app.debug') ? $exception->getMessage() : '服务器内部错误';

        return response()->json(
            ApiResponse::error(ResponseConstant::RET_UNKNOWN, $publicMessage, $reqId),
            500
        );
    }

    private static function httpExceptionResponse(HttpException $exception, string $reqId): JsonResponse
    {
        $status = $exception->getStatusCode();
        $ret = match ($status) {
            401 => ResponseConstant::RET_UNAUTHORIZED,
            403 => ResponseConstant::RET_FORBIDDEN,
            404 => ResponseConstant::RET_RESOURCE_NOT_FOUND,
            422 => ResponseConstant::RET_INVALID_PARAM,
            429 => ResponseConstant::RET_RATE_LIMITED,
            502 => ResponseConstant::RET_HTTP_5XX,
            503 => ResponseConstant::RET_SERVICE_UNAVAILABLE,
            504 => ResponseConstant::RET_HTTP_5XX,
            default => $status >= 400 && $status < 500
                ? ResponseConstant::RET_ERR
                : ResponseConstant::RET_UNKNOWN,
        };

        return response()->json(
            ApiResponse::error($ret, $exception->getMessage() ?: 'HTTP error', $reqId),
            $status
        );
    }

    private static function modelNotFoundMessage(Request $request): string
    {
        if ($request->is('api/files/*')) {
            return 'File not found.';
        }

        return 'Resource not found.';
    }

    private static function isLoginRequiredMessage(string $message): bool
    {
        return str_contains(strtolower($message), 'login required');
    }
}
