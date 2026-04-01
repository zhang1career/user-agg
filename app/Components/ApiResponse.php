<?php

namespace App\Components;

class ApiResponse
{

    /**
     * @param null $data
     * @param string $msg
     * @return array
     */
    public static function ok($data = null, string $msg = '') : array {
        return [
            'data' => $data ?? '',
            'code'  => 0,
            'msg'  => $msg
        ];
    }

    /**
     * @param int $code
     * @param string $msg
     * @return array
     */
    public static function error(int $code, string $msg) : array {
        return [
            'data' => '',
            'code'  => $code,
            'msg'  => $msg
        ];
    }

    /**
     * @param mixed $data
     */
    public static function code($data, int $code, string $msg = ''): array
    {
        return [
            'data' => $data ?? '',
            'code' => $code,
            'msg' => $msg,
        ];
    }
}
