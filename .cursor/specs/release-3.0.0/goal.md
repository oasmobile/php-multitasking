# Spec Goal: PHP 8.2 惯用法全量升级与 oasis 依赖 3.x 线

## 来源

- 分支: `release/3.0.0`
- 需求文档: 用户对话（本 release 的意图说明）

## 与实现的边界（必须遵守）

- **`goal.md` 只记录意图、范围与决策**；**不得**在「仅编写或修订 goal」时顺带修改 **`composer.json` / `composer.lock`**，也不得把 **`docs/state/`** 写成与尚未落地的依赖不一致的状态。
- **依赖升级、lock 更新、SSOT 与代码适配** 放在 **requirements / design / tasks** 及之后的实施提交中完成，并**单独 commit**。

## 背景摘要

`oasis/multitasking` 在 2.0.0 已把 **runtime 基线** 抬到 **PHP ≥ 8.2**。**Oasis 侧** 两个运行时依赖在本 release 的**目标主版本线**均为 **`^3.0`**。

**实施前提（与上游节奏对齐）**：**自开始执行 `tasks` 起**，视为 **`oasis/event` 3.0.x 已可解析**（例如已在 Packagist 发布 **v3.0.0+**，或团队保证在拆 task 前完成发布）。据此，**requirements / design / tasks** 一律以 **`oasis/event ^3.0`** 与 **`oasis/logging ^3.0`** 为输入，**不**再在本 release 的 spec 里规划 **`oasis/event ^2.0` 的过渡路径**。

- **`oasis/logging`**：实施时将 `composer` 约束调至 **`^3.0`**，按需 `composer update -W` 接纳 **monolog 3** 等传递升级，直至测试全过。
- **`oasis/event`**：实施时将 `composer` 约束调至 **`^3.0`**，按 **3.x** 的 API/类型 适配 `src/Multitasking/`（含 `WorkerManagerCompletedEvent` 等），直至 **`ut/`** 全过。

`WorkerInfo` 等仍有仅 docblock 表达的 `callable` 等，可在实施阶段用 **PHP 8.0+** 类型与惯用法收束；**不改变** 对外的可观测行为（除依赖强制的签名变更外）。

## 目标

- **依赖（Oasis 两包，均为 `^3.0`）**  
  - **`oasis/logging`**：实施时将 `composer.json` 中约束调至 **`^3.0`**，按需 `composer update -W`，跑通 **`ut/`**。  
  - **`oasis/event`**：实施时将约束调至 **`^3.0`**（**以 tasks 开始时 3.0 已可用为前提**），适配 **3.x** 后跑通 **`ut/`**。
- **PHP 8.2 惯用法（全面）**：对 `src/` 与 `ut/` 通读式整理（类型、构造、只读/可见性、去旧文件头、PHPUnit 11 习惯），**不** 用 PHP **8.3+** 专有条目，**不新增** 产品功能。
- **SSOT**：在 **依赖与 lock 已按上述策略落地之后**，再更新 **`docs/state/architecture.md`**、**`PROJECT.md`** 等与 `composer` **一致**；**`docs/state/api.md`** 若因 **event 3.x** 的基类或构造有变，在实施收尾时一并更新。

## 不做的事情（Non-Goals）

- **不改变** 现有 **公共 API** 的对外可观测行为（除 **`oasis/event` 3.x** 与 **`oasis/logging` 3.x** 强制要求的 API/兼容调整外）。
- **不新增** 产品功能、新 IPC 或新事件类型，除非为适配 3.x 所必需且语义等价。
- **不使用** PHP **8.3+** 专属语言特性；保持 **`php: >=8.2`**。

## Clarification 记录

### Q1:「全面」升级为 PHP 8.2 写法的范围？

- 回答: 依赖与 **`src/` + `ut/`** 通盘按 8.2 惯用法与类型表达整理（用户先前确认）。

### Q2: 两个 `oasis` 依赖要升到什么主版本线？

- 选项: A) 均 **^3.0** / B) 一 2.x 一 3.x / C) 其它
- 回答: **A**（用户原话：oasis 两个依赖都升级到 **^3.0**。）

### Q3: 编写 goal 时尚无 `oasis/event` 3.x 时，spec 怎么写？

- 选项: A) **以「开始 tasks 时 3.0 已可用」为前提**，不规划 2.x 过渡 / B) 在 spec 里保留 **^2.0 → ^3.0** 两阶段
- 回答: **A**（用户确认：**开始 tasks 时** 就当 **`oasis/event` 3.0 一定已有**；本 goal 与后续 requirements/design/tasks 均按 **`^3.0`** 写死。）

## 约束与决策

- **`oasis/logging`**：约束 **`^3.0`**，以 **v3.0.0+** 为实施基线。  
- **`oasis/event`**：约束 **`^3.0`**，以 **v3.0.0+** 为实施基线；**不** 在本 release 的 spec 中要求或描述 **^2.0** 过渡；**tasks 启动前** 由团队保证 **3.0.x 可解析**（发布或等价源）。  
- **发布说明 3.0.0**：应写清 **logging / event** 均已上 **3.x**，与 lock 一致。
