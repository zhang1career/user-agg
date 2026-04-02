<?php

namespace App\Http\Controllers;

use App\Services\User\UserFoundationAuthProxy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserAuthProxyController extends Controller
{
    /**
     * 无状态：将登录请求原样转发至基础用户中心，响应体（含 access_token 等字段）与下游一致，由前端保存。
     */
    public function login(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        return $proxy->forwardLogin($request);
    }

    /**
     * 无状态：`PUT /api/user/login`，与 app_user 一致；body 含 refresh_token，转发至基础用户中心（默认同路径 PUT）。
     */
    public function refresh(Request $request, UserFoundationAuthProxy $proxy): Response
    {
        return $proxy->forwardRefresh($request);
    }
}
