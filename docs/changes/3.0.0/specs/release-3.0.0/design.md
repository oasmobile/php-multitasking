# Release 3.0.0 Design

## Goal

在保持 `php >= 8.2` 前提下，完成 `oasis/logging` 与 `oasis/event` 到 `^3.0` 的升级，并让 `src/` 与 `ut/` 通过 PHP 8.2 惯用法整理后保持现有可观察行为（除依赖升级强制变更外）。

## Scope

- 包含：`composer.json`/`composer.lock` 依赖升级、`src/Multitasking` 的 event/logging 3.x 适配、`ut/` 回归修复、以及升级落地后的 SSOT 文档对齐。
- 不包含：新增产品功能、新 IPC/事件语义、PHP 8.3+ 特性改造、`oasis/event ^2 -> ^3` 过渡方案。
- 前提：按 `goal.md` Clarification，执行阶段开始时 `oasis/event 3.0.x` 已可解析。

## Requirements

1. 依赖约束必须升级为 `oasis/logging ^3.0` 与 `oasis/event ^3.0`，并能通过锁文件稳定复现安装。
2. 升级后 `ut/` 必须全量通过，且对外行为保持与当前版本一致（仅接受依赖强制的签名/类型变化）。
3. `src/` 与 `ut/` 要完成 PHP 8.2 风格收敛：优先参数/返回/属性类型、清理仅注释表达但可类型化的内容。
4. 不得引入 PHP 8.3+ 专属语法或能力。
5. SSOT 文档更新顺序必须在代码与 lock 落地之后，避免文档先于事实。

## Compatibility Statement

- 除 `oasis/event` 与 `oasis/logging` 3.x 强制要求的签名/类型调整外，本次 release 不引入新的外部可观察行为变化。
- 事件语义、IPC 语义、调度时序与日志调用语义保持现状；若上游 3.x 契约导致差异，必须在变更文档中显式标注。

## Technical Approach

### 方案选择

采用“先依赖升级并锁定，再最小差异适配代码，最后做 8.2 通读整理和 SSOT 对齐”的顺序。  
理由：先得到真实 vendor 契约，避免凭假设改动；同时降低回归定位难度。

### 关键设计决策

- 决策 1：依赖一次性升至双 `^3.0`，不设计分阶段过渡（与 `goal.md` Q2/Q3 一致）。
- 决策 2：event 相关改动只做契约兼容所需最小变更，避免顺手重构。
- 决策 3：PHP 8.2 惯用法整理不改变业务语义，只做类型与表达收敛。
- 决策 4：`docs/state/architecture.md`、`PROJECT.md`（必要时 `docs/state/api.md`）在测试通过后再更新。

### 实施路径

1. 调整 `composer` 约束并执行受控 `composer update -W`，锁定可安装依赖树。
2. 根据升级后 API 修正 `BackgroundWorkerManager`、`WorkerManagerCompletedEvent` 及相关测试。
3. 扫描 `src/Multitasking` 与 `ut/` 的 docblock-only 类型，按 8.2 能力收敛。
4. 全量运行测试后，再更新 SSOT 与发布相关文档。

### 回滚策略

- 若 `composer update -W` 后出现不可解依赖或核心测试连续失败，立即回滚到升级前 lock 快照并记录阻塞原因。
- 若阻塞来自 `oasis/event` 3.0 可用性或破坏性契约，保持 design 前提不变（不退回设计 `^2 -> ^3` 过渡），改为等待上游条件满足后重启执行。
- 回滚只针对执行阶段的代码与 lock，不回滚 `goal.md`/`design.md` 的目标决策。

### 备选方案与取舍

- 备选 A：先升 logging，再单独升 event。  
  放弃原因：会产生两轮 event 兼容窗口，测试噪音更大。
- 备选 B：仅做依赖升级，不做 8.2 风格收敛。  
  放弃原因：不满足本次 release 的明确目标。

## Impact Analysis

- 代码影响：
  - 重点模块：`src/Multitasking/BackgroundWorkerManager.php`、`src/Multitasking/WorkerManagerCompletedEvent.php`、`src/Multitasking/WorkerInfo.php`
  - 相关模块：`src/Multitasking/Semaphore.php`、`src/Multitasking/SharedMemory.php`
  - 测试：`ut/BackgroundWorkerManagerTest.php` 及全量 `ut/`
- 文档影响：
  - 必改：`PROJECT.md`、`docs/state/architecture.md`
  - 条件改：`docs/state/api.md`（当 event 3.x 导致对外文档化签名变化时）
- 风险点：
  - `oasis/event` 3.x 的构造/分发契约与当前实现差异
  - `oasis/logging` 3.x 传递到 Monolog 3 后的类型兼容
  - 类型收敛引入边界输入差异（通过回归测试控制）

## Validation Matrix

- 依赖解析：`composer validate`、`composer update -W`、干净环境 `composer install` 可复现。
- 核心事件流：`BackgroundWorkerManager` 的事件分发与 `WorkerManagerCompletedEvent` 构造契约通过测试验证。
- IPC 路径：多 worker 场景下共享内存/信号量相关测试通过。
- 日志调用：`minfo`/`mdebug` 调用点在升级后可运行且无类型错误。
- 全量回归：`ut/` 全通过，且无新增 flaky 失败。

## Risk Classification

- P0：`oasis/event` 3.x 对事件构造与 dispatcher 契约的变更。
- P1：`oasis/logging` 3.x 间接引入 Monolog 3 后的兼容性问题。
- P2：PHP 8.2 类型收敛带来的边界输入差异。

## Definition of Done

- [x] `composer.json` 与 `composer.lock` 已稳定落地双 `^3.0`。
- [x] `src/` 与 `ut/` 的必要兼容改动完成，且不引入非必要行为变化。
- [x] 全量 `ut/` 通过。
- [x] 未引入任何 PHP 8.3+ 专属特性。
- [x] `PROJECT.md` 与 `docs/state/` 按真实落地结果完成同步（`api.md` 仅在签名变化时更新）。
