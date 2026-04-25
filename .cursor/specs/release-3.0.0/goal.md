# Spec Goal: PHP 8.2 惯用法全量升级与 oasis/event 2.x

## 来源

- 分支: `release/3.0.0`
- 需求文档: 用户对话（本 release 的意图说明）

## 背景摘要

`oasis/multitasking` 在 2.0.0 已把 **runtime 基线** 抬到 **PHP ≥ 8.2**，并完成一轮现代化与测试栈升级。当前 `composer.json` 仍锁 **`oasis/event` ^1.0**，实际解析为 **v1.0.1**（2017 年发布），而上游已提供 **v2.0.0**（`php >= 8.2`），在类型与实现上与旧版可能不兼容，需要显式升级并做适配。

SSOT（`docs/state/architecture.md` / `docs/state/api.md`）仍把事件机制记为 `oasis/event ^1.0`；`WorkerInfo` 等类里仍有 **仅 docblock 描述** 的 `callable` 等写法，可进一步用 **PHP 8.0+ 起可用的类型与惯用法** 收束，使全库（`src/` 与 `ut/`）在 **8.2 可运行** 的前提下，**风格与类型表达** 与 8.2 时代一致。

本 spec 将 **3.0.0** 定位为：在 **不改变已有公共行为语义** 的前提下，**全量代码用 PHP 8.2 惯用法整理**，并 **升级并适配 `oasis/event` 2.x**。

## 目标

- **依赖**：将 `composer.json` 中 `oasis/event` 从 `^1.0` 调整为 **`^2.0`**，执行 `composer update oasis/event` 并更新 `composer.lock`；若 2.x 有 **构造器、基类 `Oasis\Mlib\Event\Event`、事件分发 API** 等变更，在 `src/Multitasking/`（含 `WorkerManagerCompletedEvent` 等）中完成适配，直至 **全量测试通过**。
- **PHP 8.2 惯用法（全面）**：
  - 为仍依赖 `@var` 的成员补充 **原生日类型**（如 `WorkerInfo` 的 `callable` 等，在 8.0+ 合法处使用 **typed property** 或构造器提升等惯用法，避免无类型属性）。
  - 统一可改进处的 **只读/可见性/构造器提升/枚举式常量** 等（在不改变对外的行为与 observable 效果的前提下）；删除或收束过时的 **文件头注释**（如旧 PhpStorm 模板块），与当前仓库风格一致。
  - 对 `src/` 与 `ut/` 做一次 **8.2 向** 的通读式整理：**可读性、类型表达、** 与 PHPUnit 11 的写法一致，无新增产品功能。
- **SSOT**：更新 `docs/state/architecture.md`（及任何仍写 `^1.0` 的索引，如 `PROJECT.md` 若需要）中 **`oasis/event` 版本约束** 与事件机制说明，使其与 `composer.json` 及实现一致；若 `docs/state/api.md` 中事件基类/命名空间/构造签名因 2.x 有变，一并更新。
- **质量**：`ut/` 在升级后 **全部通过**；必要时为 `oasis/event` 适配补充 **回归测试**（仍放在既有 `ut/` 约定下）。

## 不做的事情（Non-Goals）

- **不改变** 现有 **公共 API 的对外行为**（可观测的 fork/wait/IPC/事件名与语义）；除 **`oasis/event` 2.x 强制要求的签名/类型** 外，不引入对调用方无必要的 breaking 行为变化。
- **不新增** 产品功能、新 IPC 机制、新事件类型（除非为适配 2.x 所必需且等价替代）。
- **不使用** PHP **8.3+** 专属语言特性（以 **8.2** 为语言上限，与当前 `require.php` 一致），避免 8.2 环境无法运行。
- **不** 在本 spec 中顺带大改 `oasis/logging` 或其它运行时依赖，除非为解除与 `oasis/event` 2.x 的冲突所必需（若出现，在 spec 执行阶段再单独记决策）。

## Clarification 记录

### Q1:「全面」升级为 PHP 8.2 写法的范围？

- 选项: A) 仅动 `composer.json` / 锁文件，不改源码风格 / B) 依赖与 **`src/` + `ut/`** 通盘按 8.2 惯用法与类型表达整理 / C) 只修 `composer update` 后报的兼容性问题
- 回答: **B**（用户原话：全面代码升级为 PHP 8.2 的写法。）

### Q2: `oasis/event` 要升到哪一主版本线？

- 选项: A) 保持 1.x / B) 升级到已发布的 **2.x**（Packagist 上 **v2.0.0**，`php >= 8.2`）/ C) 使用某个 git 引用或 dev 分支
- 回答: **B**（用户原话：也要更新一下 oasis/event；与 8.2 基线一致，以 **^2.0** 为约束。）

### Q3: 本 release 对下游的兼容性预期？

- 选项: A) 作为 **3.0.0 major**，允许与 **仍依赖 `oasis/event` 1.x 的应用** 无法并存或需其同步升级 / B) 必须同时兼容 1.x 与 2.x 的消费者
- 回答: **A** — 版本号 **3.0.0** 与 **`oasis/event` 2.x** 的升级视为 **可 breaking**；不要求与锁在 1.x 的上游**无感**混用（若需并存，由使用方在依赖解析层面处理）。

## 约束与决策

- **语言级别**：`php` 要求保持 **`>=8.2`**；实现与 CI 以 **8.2** 为**最低**受支持版本。
- **事件包**：`oasis/event` 约束为 **`^2.0`**，以 **v2.0.0+** 为准进行适配与文档更新。
- **语义**：在 **事件包 API 适配** 之外，**不** 为「现代化」而改动 `BackgroundWorkerManager` / IPC 的对外契约；若有疑似行为变化，必须在 spec 设计/测试阶段显式证明等价。
- **发布说明**：3.0.0 的 changelog 中应 **突出** `oasis/event` **主版本** 与 **全库 8.2 惯用法** 两类变更，方便下游升级。
