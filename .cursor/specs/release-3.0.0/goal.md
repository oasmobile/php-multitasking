# Spec Goal: PHP 8.2 惯用法全量升级与 oasis 依赖 3.x 线

## 来源

- 分支: `release/3.0.0`
- 需求文档: 用户对话（本 release 的意图说明）

## 背景摘要

`oasis/multitasking` 在 2.0.0 已把 **runtime 基线** 抬到 **PHP ≥ 8.2**。**Oasis 侧** 两个运行时依赖需对齐到 **^3.0** 主版本线：

- **`oasis/logging`**：已有 **v3.0.0**（`composer` 可约束为 `^3.0`；会拉 **monolog 3** 等传递升级）。
- **`oasis/event`**：截至当前在 **Packagist** 上**最高**为 **v2.0.0**，**尚无** 3.x tag，因此 `composer require oasis/event:^3.0` **无法解析**；在 **php-event 发布 v3.0.0 并上 Packagist 之前**，`composer.json` 中**可立即落地**的约束为 **`^2.0`**（与 PHP 8.2 基线一致）。**目标版本线**仍定为 **`^3.0`**：一旦 **v3.0.0** 可用，将约束从 `^2.0` 改为 `^3.0` 并完成 **API/类型** 适配，使 **实际 lock 与「两包均 ^3.0」** 一致。

`WorkerInfo` 等仍有仅 docblock 表达的 `callable` 等，可继续用 **PHP 8.0+** 类型与惯用法收束；**不改变** 对外的可观测行为（除依赖强制的签名变更外）。

## 目标

- **依赖（Oasis 两包，目标 `^3.0`）**  
  - **`oasis/logging`**：在 `composer.json` 中保持 **`^3.0`**，`composer update -W` 以接纳 **monolog 3** 等，直至 **全量测试通过**（**已完成一版**）。  
  - **`oasis/event`**：**目标** 约束 **`^3.0`**；在 **v3.0.0 发布前**，`composer.json` 使用 **`^2.0`** 与当前 Packagist 事实一致；**v3.0.0 发布之后**：改为 **`^3.0`**，在 `src/Multitasking/`（含 `WorkerManagerCompletedEvent` 等）按 3.x 做适配，再跑通 `ut/`。
- **PHP 8.2 惯用法（全面）**：对 `src/` 与 `ut/` 通读式整理（类型、构造、只读/可见性、去旧文件头、PHPUnit 11 习惯），**不** 用 PHP **8.3+** 专有条目，**不新增** 产品功能。
- **SSOT**：`docs/state/architecture.md`、根目录 **`PROJECT.md`**（若出现版本行）等，与 `composer.json` 中**实际**约束与 lock 一致地更新（含 **`oasis/logging` ^3.0**、**`oasis/event` 当前为 ^2.0；event 3.x 合入后改为 ^3.0** 的说明）。`docs/state/api.md` 若因 event 2.x/3.x 的基类或构造有变，一并更新。

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
- 回答: **A**（与当前可解析的 Composer 元数据一致，避免主分支无法 `install`。）

## 约束与决策

- **`oasis/logging`**：约束 **`^3.0`**，以 **v3.0.0+** 为实施基线。  
- **`oasis/event`**：  
  - **政策目标**：`^3.0`；  
  - **当前**（v3.0.0 未在 Packagist 时）：`^2.0`（v2.0.0+）；  
  - **v3.0.0 可用后**：`composer` 与 SSOT 同步改为 **`^3.0`**，并做一次 **event-3** 的适配与回归。  
- **发布说明 3.0.0** 中应写清：**logging 已上 3.x**；**event 以 2.0.0+ 为当前事实依赖**，并预告 **待 event 3.x 并入后的二次约束**（或在一个 PR 中同步发布 event 3.0.0 与 multitasking 3.0.0，使对外叙事统一为两包 3.x）。
