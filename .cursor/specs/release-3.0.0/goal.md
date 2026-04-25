# Spec Goal: PHP 8.2 惯用法全量升级与 oasis 依赖 3.x 线

## 来源

- 分支: `release/3.0.0`
- 需求文档: 用户对话（本 release 的意图说明）

## 与实现的边界（必须遵守）

- **`goal.md` 只记录意图、范围与决策**；**不得**在「仅编写或修订 goal」时顺带修改 **`composer.json` / `composer.lock`**，也不得把 **`docs/state/`** 写成与尚未落地的依赖不一致的状态。
- **依赖升级、lock 更新、SSOT 与代码适配** 放在 **requirements / design / tasks** 及之后的实施提交中完成，并**单独 commit**。

## 背景摘要

`oasis/multitasking` 在 2.0.0 已把 **runtime 基线** 抬到 **PHP ≥ 8.2**。**Oasis 侧** 两个运行时依赖在本 release 的**目标主版本线**为 **`^3.0`**：

- **`oasis/logging`**：Packagist 上已有 **v3.0.0**，实施阶段将 `composer` 约束调至 **`^3.0`** 并 `composer update -W` 接纳 **monolog 3** 等传递升级，直至测试全过。
- **`oasis/event`**：截至编写本 goal，Packagist **最高**为 **v2.0.0**，**尚无** 3.x；实施阶段在 **v3.0.0 发布前** 可将约束定为 **`^2.0`**（可解析且与 PHP 8.2 一致）；**v3.0.0 可用后** 改为 **`^3.0`** 并完成 **API/类型** 适配，使 lock 与「两包均 **^3.0**」的最终策略一致。

`WorkerInfo` 等仍有仅 docblock 表达的 `callable` 等，可在实施阶段用 **PHP 8.0+** 类型与惯用法收束；**不改变** 对外的可观测行为（除依赖强制的签名变更外）。

## 目标

- **依赖（Oasis 两包，目标 `^3.0`）**  
  - **`oasis/logging`**：实施时将 `composer.json` 中约束调至 **`^3.0`**，按需 `composer update -W`，跑通 **`ut/`**。  
  - **`oasis/event`**：**目标** **`^3.0`**；在 **v3.0.0 发布前** 实施 **`^2.0`**；发布后改为 **`^3.0`** 并适配 `src/Multitasking/`（含 `WorkerManagerCompletedEvent` 等），再跑通 **`ut/`**。
- **PHP 8.2 惯用法（全面）**：对 `src/` 与 `ut/` 通读式整理（类型、构造、只读/可见性、去旧文件头、PHPUnit 11 习惯），**不** 用 PHP **8.3+** 专有条目，**不新增** 产品功能。
- **SSOT**：在 **依赖与 lock 已按上述策略落地之后**，再更新 **`docs/state/architecture.md`**、**`PROJECT.md`** 等与 `composer` **一致**；**`docs/state/api.md`** 若因 event 2.x/3.x 的基类或构造有变，在实施收尾时一并更新。

## 不做的事情（Non-Goals）

- **不改变** 现有 **公共 API** 的对外可观测行为（除 **`oasis/event` 2.x/3.x** 强制要求的 API 与 **`oasis/logging` 3.x** 的兼容调整外）。
- **不新增** 产品功能、新 IPC 或新事件类型，除非为适配 3.x 所必需且语义等价。
- **不使用** PHP **8.3+** 专属语言特性；保持 **`php: >=8.2`**。

## Clarification 记录

### Q1:「全面」升级为 PHP 8.2 写法的范围？

- 回答: 依赖与 **`src/` + `ut/`** 通盘按 8.2 惯用法与类型表达整理（用户先前确认）。

### Q2: 两个 `oasis` 依赖要升到什么主版本线？

- 选项: A) 均 **^3.0** / B) 一 2.x 一 3.x / C) 其它
- 回答: **A**（用户原话：oasis 两个依赖都升级到 **^3.0**。）

### Q3: `oasis/event` 尚无 Packagist 3.x 时怎么落地？

- 选项: A) 在 **event v3.0.0 发布前**，`composer` 暂用 **^2.0**，goal 与 SSOT 标明「目标 **^3.0**，待上游发布后再改约束与适配」/ B) 用 **VCS / path** 引用未发版的 3.0
- 回答: **A**（与当前可解析的 Composer 元数据一致，避免无法 `install`。）

## 约束与决策

- **`oasis/logging`**：目标约束 **`^3.0`**，以 **v3.0.0+** 为实施基线。  
- **`oasis/event`**：  
  - **政策目标**：`^3.0`；  
  - **在 v3.0.0 未发布时**：实施约束 **`^2.0`**（v2.0.0+）；  
  - **v3.0.0 可用后**：`composer` 与 SSOT 同步改为 **`^3.0`**，并做 **event-3** 适配与回归。  
- **发布说明 3.0.0**：应写清 **logging / event** 的版本策略（含 event 在 3.x 发布前后的过渡），避免与 lock 事实脱节。
