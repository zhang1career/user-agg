<?php

namespace App\Http\Controllers;

use App\Services\User\UserFoundationAuthProxy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserAuthProxyController extends Controller
{
    public function register(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__]);

        return $proxy->forwardRegister($request);
    }

    public function registerVerify(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__]);

        return $proxy->forwardRegisterVerify($request);
    }

    /**
     * 无状态：将登录请求原样转发至基础用户中心，响应体（含 access_token 等字段）与下游一致，由前端保存。
     */
    public function login(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__]);

        return $proxy->forwardLogin($request);
    }

    /**
     * 无状态：`PUT /api/user/login`，与 app_user 一致；body 含 refresh_token，转发至基础用户中心（默认同路径 PUT）。
     */
    public function refresh(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__]);

        return $proxy->forwardRefresh($request);
    }

    public function resetPassword(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__]);

        return $proxy->forwardResetPasswordRequest($request);
    }

    public function resetPasswordVerify(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        $this->logHandledApiRequest($request, ['handler' => __FUNCTION__]);

        return $proxy->forwardResetPasswordVerify($request);
    }
}
