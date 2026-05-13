<?php

declare(strict_types=1);

namespace App\Http\Client;

use Illuminate\Http\Request;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Propagates the inbound HTTP {@code X-Request-Id} header to every outbound
 * Laravel HTTP client call. Outside an HTTP request, mints a short-lived id.
 */
final class OutboundRequestIdMiddleware
{
    public const HEADER = 'X-Request-Id';

    public static function addHeader(RequestInterface $request): RequestInterface
    {
        if ($request->hasHeader(self::HEADER)) {
            return $request;
        }

        $id = self::currentRequestId();
        if ($id === '') {
            return $request;
        }

        return $request->withHeader(self::HEADER, $id);
    }

    private static function currentRequestId(): string
    {
        try {
            if (! app()->bound('request')) {
                return self::mintFallback();
            }
            $req = app('request');
            if (! $req instanceof Request) {
                return self::mintFallback();
            }

            $header = $req->header(self::HEADER);
            if (is_string($header) && $header !== '') {
                return $header;
            }
        } catch (Throwable) {
        }

        return self::mintFallback();
    }

    private static function mintFallback(): string
    {
        try {
            $bytes = random_bytes(8);

            return 'oid-'.bin2hex($bytes);
        } catch (Throwable) {
            return 'oid-'.dechex((int) (microtime(true) * 1000)).'-'.dechex(mt_rand(0, 0xFFFFFF));
        }
    }
}
