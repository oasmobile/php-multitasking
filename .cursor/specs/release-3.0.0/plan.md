# Release 3.0.0 Implementation Plan

> **Execution:** 使用 Cursor Plan Mode 逐 task 执行；可并行的 task 通过 sub-agent 并行派发。详见 `spec-execution` 规则。

**Goal:** 将 `oasis/logging` 与 `oasis/event` 升级到 `^3.0`，完成 `src/` 与 `ut/` 的 PHP 8.2 惯用法收敛，并在验证通过后同步 SSOT。  
**Architecture:** 依赖升级 -> 最小差异兼容 -> 类型收敛 -> 全量验证 -> 文档对齐。  
**Tech Stack:** PHP 8.2+, Composer, PHPUnit 11, oasis/event 3.x, oasis/logging 3.x。

## File Structure

- `composer.json`：升级 `oasis/logging` 与 `oasis/event` 的版本约束。
- `composer.lock`：锁定升级后的可复现依赖树。
- `src/Multitasking/BackgroundWorkerManager.php`：event dispatcher/logging 调用兼容改造。
- `src/Multitasking/WorkerManagerCompletedEvent.php`：event 基类与构造契约兼容。
- `src/Multitasking/WorkerInfo.php`：docblock-only 类型向 PHP 8.2 类型表达收敛。
- `src/Multitasking/Semaphore.php`：日志调用与类型细节回归修正（若触发）。
- `src/Multitasking/SharedMemory.php`：日志调用与类型细节回归修正（若触发）。
- `src/Multitasking/MessageQueue.php`：类型收敛扫描（按测试结果决定是否修改）。
- `ut/BackgroundWorkerManagerTest.php`：event 3.x 契约回归与断言更新。
- `ut/MessageQueueTest.php`、`ut/MessageQueuePbtTest.php`：消息队列行为回归验证。
- `ut/SemaphoreTest.php`、`ut/SemaphorePbtTest.php`：信号量行为回归验证。
- `ut/SharedMemoryTest.php`、`ut/SharedMemoryPbtTest.php`：共享内存行为回归验证。
- `ut/bootstrap.php`：测试启动兼容（仅在依赖升级导致失败时修改）。
- `docs/state/architecture.md`：依赖与架构事实同步。
- `docs/state/api.md`：仅在 event 3.x 导致文档化签名变化时更新。
- `PROJECT.md`：项目依赖现状与版本信息同步。
- `docs/changes/3.0.0/`：release 变更记录与兼容说明（按仓库既有惯例补齐）。

## - [ ] Task 1: 依赖升级与锁文件落地

- [ ] **1.1 — 更新 Composer 约束**
  - 修改：`composer.json`
  - 内容：将 `oasis/logging`、`oasis/event` 约束改为 `^3.0`，不引入其他无关依赖变更。
- [ ] **1.2 — 生成并校验锁文件**
  - 修改：`composer.lock`
  - 命令：
    - `composer validate`
    - `composer update -W oasis/logging oasis/event`
    - `composer install`
  - 预期：依赖可解析，lock 可复现安装，无解析冲突。
- [ ] **1.3 — 基线测试并记录失败面**
  - 修改：无（日志级观察）
  - 命令：`vendor/bin/phpunit`
  - 预期：允许出现失败，但失败应集中在 event/logging 契约影响面，为后续 task 提供修复入口。
- [ ] **1.4 — 提交 Task 1**
  - 修改：`composer.json`、`composer.lock`
  - 验证：`git diff --name-only --cached` 仅包含依赖文件。

## - [ ] Task 2: event/logging 3.x 契约兼容改造

- [ ] **2.1 — 修正 dispatcher/event 关键实现**
  - 修改：`src/Multitasking/BackgroundWorkerManager.php`、`src/Multitasking/WorkerManagerCompletedEvent.php`
  - 内容：按升级后 vendor 契约调整构造参数、分发调用、类型签名，保持业务语义不变。
- [ ] **2.2 — 收敛 logging 调用兼容点**
  - 修改：`src/Multitasking/BackgroundWorkerManager.php`、`src/Multitasking/Semaphore.php`、`src/Multitasking/SharedMemory.php`
  - 内容：修复 logging 3.x/Monolog 3 触发的调用或类型问题，保持原日志语义。
- [ ] **2.3 — 对齐核心测试断言**
  - 修改：`ut/BackgroundWorkerManagerTest.php`
  - 内容：将测试断言同步到 event 3.x 契约（对象类型、事件触发路径、返回/行为断言）。
- [ ] **2.4 — 更新受影响测试文件**
  - 修改：`ut/MessageQueueTest.php`、`ut/MessageQueuePbtTest.php`、`ut/SemaphoreTest.php`、`ut/SemaphorePbtTest.php`、`ut/SharedMemoryTest.php`、`ut/SharedMemoryPbtTest.php`、`ut/bootstrap.php`（仅必要时）
  - 内容：仅修复与 event/logging 依赖行为变化直接相关的测试失败。
- [ ] **2.5 — 运行全量测试检查点**
  - 修改：无
  - 命令：`vendor/bin/phpunit`
  - 预期：全量通过，输出干净（无 warning/deprecation/异常堆栈）。
- [ ] **2.6 — 提交 Task 2**
  - 修改：仅 event/logging 兼容相关源码与测试文件
  - 验证：提交前确认未夹带 SSOT 或无关风格改动。

## - [ ] Task 3: PHP 8.2 惯用法语法升级

`[Parallel: 3.1, 3.2]`

- [ ] **3.1 — 收敛核心类型表达**
  - 修改：`src/Multitasking/WorkerInfo.php`、`src/Multitasking/MessageQueue.php`
  - 内容：将可安全收敛的 docblock-only 类型转为参数/返回/属性类型，避免行为变化。
- [ ] **3.2 — 全量扫描 src 与 ut 的 8.2 语法一致性**
  - 修改：`src/Multitasking/*.php`、`ut/*.php`（仅必要文件）
  - 内容：清理仅注释表达但可类型化的位置，统一为 PHP 8.2 可用写法；禁止引入 8.3+ 特性。
- [ ] **3.3 — 补齐回归测试与断言**
  - 修改：`ut/MessageQueueTest.php`、`ut/MessageQueuePbtTest.php`、`ut/SemaphoreTest.php`、`ut/SemaphorePbtTest.php`、`ut/SharedMemoryTest.php`、`ut/SharedMemoryPbtTest.php`
  - 内容：仅为类型收敛引发的行为边界补充断言，不扩大业务范围。
- [ ] **3.4 — 运行全量测试检查点**
  - 修改：无
  - 命令：`vendor/bin/phpunit`
  - 预期：全量通过，输出干净（无 warning/deprecation/异常堆栈）。
- [ ] **3.5 — 提交 Task 3**
  - 修改：仅 8.2 语法收敛相关源码与测试文件
  - 验证：确认不包含依赖与文档文件。

## - [ ] Task 4: SSOT 与发布文档同步

- [ ] **4.1 — 同步项目依赖事实**
  - 修改：`PROJECT.md`、`docs/state/architecture.md`
  - 内容：将 event/logging 版本线与架构叙述同步到已落地事实。
- [ ] **4.2 — 条件同步 API 文档**
  - 修改：`docs/state/api.md`（仅当签名发生文档化差异）
  - 内容：更新 `BackgroundWorkerManager` 与 `WorkerManagerCompletedEvent` 相关描述。
- [ ] **4.3 — 更新 release changes**
  - 修改：`docs/changes/3.0.0/` 下相关文档
  - 内容：记录升级范围、兼容性说明、已知风险与回滚参考。
- [ ] **4.4 — 文档一致性检查**
  - 修改：无
  - 命令：`rg "oasis/(event|logging)" docs/state PROJECT.md docs/changes/3.0.0`
  - 预期：版本描述一致，无旧版本残留误报。
- [ ] **4.5 — 提交 Task 4**
  - 修改：仅文档文件
  - 验证：不包含源码与 lock 变更。

## - [ ] Task 5: 手工测试与发布稳定性检查

- [ ] **5.1 — Increment alpha tag**
  - 修改：git tag
  - 命令：
    - `git tag -l "v3.0.0-alpha.*"`
    - 依据最大序号 +1 创建新 tag（例如 `v3.0.0-alpha.1`）。
  - 预期：新 alpha tag 成功创建；从打 tag 到本 task 结束前不做 commit。
- [ ] **5.2 — 干净环境安装验证**
  - 修改：无
  - 命令：`composer install --no-interaction`
  - 预期：可安装成功，无依赖冲突。
- [ ] **5.3 — 回归测试复跑**
  - 修改：无
  - 命令：`vendor/bin/phpunit`
  - 预期：全量通过，输出干净。
- [ ] **5.4 — 记录手工测试结论**
  - 修改：`docs/changes/3.0.0/` 下测试记录文档
  - 内容：记录 alpha tag、执行命令、结果与遗留风险（如有）。
- [ ] **5.5 — 提交 Task 5**
  - 修改：仅手工测试记录文档
  - 验证：确认在 tag 后无额外违规 commit。

## - [ ] Task 6: Code Review 收口

- [ ] **6.1 — 派发 code-reviewer sub-agent**
  - 修改：无（审查阶段）
  - 内容：按当前分支 diff 做逐文件审查，聚焦行为回归、边界条件、错误处理与命名一致性。
- [ ] **6.2 — 修复 review 问题并复验**
  - 修改：review 指出的目标文件
  - 命令：`vendor/bin/phpunit`
  - 预期：问题关闭后测试仍全绿。
- [ ] **6.3 — 最终发布前检查与提交**
  - 修改：`plan.md` 勾选状态 + 必要修复
  - 命令：
    - `git status`
    - `git log --oneline -n 10`
  - 预期：工作树干净，commit 粒度与 task 对齐，可进入 finish 流程。

## Socratic Review

- 问：plan 是否覆盖了 design 的全部要求？  
  答：是。依赖升级、event/logging 兼容、8.2 语法升级、SSOT 顺序与 DoD 均映射到 Task 1-6。
- 问：是否满足手工测试规则？  
  答：是。手工测试单独为一个 top-level task，且首步是 Increment alpha tag。
- 问：是否存在占位符或不可执行步骤？  
  答：无。每个 step 都指定了目标文件、动作和验证命令。
- 问：是否把实现代码写进 plan？  
  答：否。plan 仅描述做什么、改哪里、测什么，符合 writing-plans override。

### Review Log

- 2026-04-26：基于 `goal.md` 与 `design.md` 生成初版 `plan.md`，满足 release 分支手工测试与 code review 收口约束。
- 2026-04-26：按用户反馈拆分出独立 Task 4（PHP 8.2 惯用法语法升级），并顺延后续任务编号。
- 2026-04-26：按用户反馈合并原 Task 2（event）与 Task 3（logging）为统一契约兼容任务，并重排后续编号。
