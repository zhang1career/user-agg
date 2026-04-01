# User Aggregation Middleware Design

## 1. 背景与目标

本项目是用户信息聚合中间件：

- 上游：前端应用。
- 下游：
  - 用户基础服务（注册、登录、登出、用户基础资料等，参考 `app_user`）。
  - 用户业务服务（按业务域扩展，逐步接入）。

核心目标是让“业务服务接入聚合中间件”遵循统一接口契约，从而支持多业务服务并行接入，降低聚合层与业务侧耦合。

## 2. 核心设计原则

- 契约优先：先定义稳定的接入接口，再由各业务服务实现。
- 配置驱动：通过配置声明已接入服务，避免硬编码依赖。
- 聚合编排：聚合层只负责编排与拼装，不承载业务细节。
- 渐进演进：先在本仓实现可运行接口，再抽象到共享仓库 `paganini` 复用。
- 向后兼容：为未来新增业务服务预留扩展点，不影响已接入服务。

## 3. 架构分层（中间件侧）

- Foundation Gateway 层
  - 负责调用用户基础服务（如用户身份、基础资料）。
  - 隔离外部 HTTP 细节（URL、鉴权、超时、错误映射）。
- Business Contract 层
  - 定义业务服务接入契约（服务标识、适配判断、数据获取）。
  - 每个业务域提供自己的实现类。
- Registry 层
  - 从配置装配业务服务实现。
  - 在运行时筛选可用服务并按统一流程调用。
- Aggregation Orchestrator/Controller 层
  - 先取基础用户信息，再并行/串行聚合业务数据。
  - 输出统一响应结构给前端。

## 4. 统一接入契约（第一版）

建议契约能力包含：

- `serviceKey()`：返回业务服务唯一标识（用于响应分区与观测）。
- `supports(context)`：判断当前请求场景是否需要接入此服务。
- `fetch(baseUser, context)`：基于基础用户信息拉取该业务域数据并返回结构化结果。

约束：

- 返回值必须是可序列化结构（数组/标量/嵌套对象）。
- 不抛出不可控异常到控制器层；异常需在实现内做语义化包装或在聚合层统一降级。
- 业务服务不直接依赖控制器对象，仅依赖契约输入。

## 5. 响应模型建议

聚合返回建议分为三块：

- `user`：基础服务返回的统一用户基础信息。
- `biz`：按 `serviceKey` 分组的业务数据。
- `meta`：请求追踪与降级信息（可选，如 timeout/degraded/services_used）。

示例（逻辑形态）：

```json
{
  "user": { "...": "..." },
  "biz": {
    "coupon": { "...": "..." },
    "membership": { "...": "..." }
  },
  "meta": {
    "degraded": false,
    "services_used": ["coupon", "membership"]
  }
}
```

下游协议约定（当前已确认）：

- 用户基础服务 `app_user` 的当前用户接口：`/api/user/me`；
- 基础服务返回结构：`{ "data": { ... } }`；
- 后续业务服务统一返回结构：`{ "data": { ... } }`；
- 聚合层内部统一解包 `data` 再做聚合，避免上游感知下游包装细节。

## 6. 错误与降级策略

- 基础服务失败：默认整体失败（无用户主身份无法聚合）。
- 单业务服务失败：
  - 聚合层负责降级处理，且“部分失败”返回业务错误码（非 0）。
  - 降级策略可配置（建议：`mask_null` / `mask_error_object` / `fail_fast`）。
  - `mask_null`：失败分区返回 `null`；
  - `mask_error_object`：失败分区返回统一错误对象；
  - `fail_fast`：任一业务分区失败则聚合请求失败。
- 超时：
  - 每个下游服务设置独立超时；
  - 聚合层设置总超时预算（后续可演进）。

## 7. 可观测性要求

- 记录请求级 trace id（透传或生成）。
- 记录各下游耗时、成功率、降级次数。
- 为每个 `serviceKey` 输出最小可观测指标。

## 8. 抽象到 `paganini` 的边界

`paganini` 应承载“跨项目复用且稳定”的抽象：

- 业务服务接入契约接口定义；
- 契约相关 DTO/上下文对象；
- 默认注册器；
- 默认异常类型；
- 通用降级策略组件（策略解析、默认策略实现、错误码约定）。

业务项目中仅保留：

- 契约实现（具体业务逻辑）；
- 本地配置（启用哪些服务、下游地址）；
- 聚合编排细节（如路由、鉴权策略）。

## 9. 实施阶段规划

- Phase 1（当前仓）
  - 落地契约接口、注册器、基础聚合入口与配置化接入；
  - 提供 HTTP 业务适配器基类（统一 HTTP 调用 + `{data}` 解包）；
  - 提供一个可配置业务服务实现示例（`account_profile`）；
  - 保持与现有代码风格一致。
- Phase 2（`paganini`）
  - 抽取契约与通用组件；
  - 在当前仓替换为依赖 `paganini` 包。
- Phase 3（多业务接入）
  - 逐个接入真实业务服务；
  - 补齐 SLA、超时、熔断与观测。

## 10. 关于当前代码来源

本仓代码来自其他工程拷贝，不要求保留历史实现。可把现有代码当作风格参考，但以本设计目标为准做必要重构与替换。

## 11. 当前落地状态

已在 `paganini` 落地可复用抽象（首版）：

- `Paganini\UserAggregation\Contracts\BusinessServiceContract`
- `Paganini\UserAggregation\Contracts\DegradePolicyContract`
- `Paganini\UserAggregation\DTO\RequestContext`
- `Paganini\UserAggregation\DTO\AggregationResult`
- `Paganini\UserAggregation\Registry\BusinessServiceRegistry`
- `Paganini\UserAggregation\Execution\AggregationExecutor`
- `Paganini\UserAggregation\Policies\DefaultDegradePolicy`
- `Paganini\UserAggregation\Exceptions\AggregationException`
- `Paganini\UserAggregation\Exceptions\DownstreamServiceException`
- `Paganini\UserAggregation\Support\DownstreamPayload`

`user-agg` 已切换为依赖 `paganini` 以上抽象，并保留本地网关/适配器实现作为项目侧编排层。

## 12. 新业务服务接入步骤（标准化）

1. 新建业务插件类，继承 `HttpBusinessServiceAdapter` 并实现：
   - `serviceKey()`
   - `configKey()`
   - 可选 `endpoint()` / `query()` / `mapData()`
2. 在 `config/user_agg.php` 的 `business_services` 中注册该类；
3. 在 `config/user_agg.php` 的 `downstream.<service_key>` 增加 `base_url`、`endpoint`、`timeout_seconds`；
4. 在 `.env` 填充对应下游配置；
5. 根据场景设置：
   - `USER_AGG_EXECUTION_MODE=serial|parallel`
   - `USER_AGG_DEGRADE_STRATEGY=mask_null|mask_error_object|fail_fast`
