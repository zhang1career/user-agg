# User Aggregation

用户信息聚合中间件

## API

- `GET /api/user/me`

## Downstream Contract

所有下游服务（基础服务与业务服务）统一返回：

```json
{
  "errorCode": 0,
  "data": {},
  "message": ""
}
```

聚合层内部统一解包 `data` 后再进行聚合。

## Configuration

基础服务：

- `API_GATEWAY_BASE_URL`
- `USER_CENTER_ME_ENDPOINT=/api/user/me`
- `USER_CENTER_TIMEOUT_SECONDS=3`

业务服务（示例：`account_profile`）：

- `USER_BIZ_ACCOUNT_PROFILE_ENABLED=true`
- `USER_BIZ_ACCOUNT_PROFILE_BASE_URL`
- `USER_BIZ_ACCOUNT_PROFILE_ENDPOINT=/api/user/profile`
- `USER_BIZ_ACCOUNT_PROFILE_TIMEOUT_SECONDS=3`

业务服务（示例：`membership_tier`）：

- `USER_BIZ_MEMBERSHIP_TIER_ENABLED=false`
- `USER_BIZ_MEMBERSHIP_TIER_BASE_URL`
- `USER_BIZ_MEMBERSHIP_TIER_ENDPOINT=/api/user/membership`
- `USER_BIZ_MEMBERSHIP_TIER_TIMEOUT_SECONDS=3`

聚合执行与降级：

- `USER_AGG_EXECUTION_MODE=serial|parallel`
- `USER_AGG_DEGRADE_STRATEGY=mask_null|mask_error_object|fail_fast`
- `USER_AGG_PARTIAL_FAILURE_CODE=20601`
- `USER_AGG_PARTIAL_FAILURE_MESSAGE=Partially failed, degraded by aggregator.`

## Quick Start

1. 配置 `.env` 中的基础服务和业务服务地址。
2. 启动服务：`php artisan serve`
3. 请求聚合接口：

```bash
curl -H "Authorization: Bearer <access_token>" \
     -H "X-Trace-Id: test-trace-001" \
     http://127.0.0.1:8000/api/user/me
```

## Response Shape

成功（全部成功）：

```json
{
  "code": 0,
  "msg": "",
  "data": {
    "user": {},
    "biz": {
      "account_profile": {}
    },
    "meta": {
      "degraded": false,
      "degraded_services": [],
      "services_used": ["account_profile"],
      "degrade_strategy": "mask_null",
      "execution_mode": "serial"
    }
  }
}
```

部分失败（聚合层降级）：

- `code` 使用 `USER_AGG_PARTIAL_FAILURE_CODE`；
- 失败服务名在 `data.meta.degraded_services`；
- 每个失败分区值由降级策略决定（`null` / 错误对象 / fail fast）。
