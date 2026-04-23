# Implementation Plan: PHP 8 升级 (oasis/multitasking 2.0.0)

## Overview

将 `oasis/multitasking` 从 PHP 5.6 / PHPUnit 5 迁移至 PHP 8.2+ / PHPUnit 11，对源代码进行 PHP 8 现代化改造，并引入 Property-Based Testing。

改造按依赖关系排序，确保每一步完成后测试可运行：Composer 依赖 → PHPUnit 配置 → 测试基类 → 源代码逐文件改造（IPC 组件 → Worker 组件）→ PBT 测试 → SSOT 文档更新 → 手工测试 → Code Review。

## Tasks

- [x] 1. Composer 依赖版本升级
  - [x] 1.1 修改 `composer.json`：`require.php` 从 `>=5.6.1` 改为 `>=8.2`；`require-dev.phpunit/phpunit` 从 `^5.5` 改为 `^11.0`；新增 `require-dev.giorgiosironi/eris`: `^0.14.0`；保留现有 `require` 依赖（`ext-pcntl`、`oasis/logging`、`oasis/event`）不变
    - _Ref: Requirement 1, AC 1-4_
  - [x] 1.2 执行 `composer update` 确认依赖解析无冲突
    - _Ref: Requirement 1, AC 5_
  - [x] 1.3 Checkpoint: 执行 `composer install` 确认无冲突，commit

- [x] 2. PHPUnit 配置与测试基类适配
  - [x] 2.1 更新 `phpunit.xml` 至 PHPUnit 11 schema：`xsi:noNamespaceSchemaLocation` 更新为 `vendor/phpunit/phpunit/phpunit.xsd`；移除 `enforceTimeLimit` 属性（PHPUnit 11 已移除）；testsuite 从逐文件 `<file>` 列举改为 `<directory>ut</directory>`（PHPUnit 默认匹配 `*Test.php` 后缀）
    - _Ref: Requirement 2, AC 1_
  - [x] 2.2 适配 4 个测试文件的 PHPUnit 11 基类：`ut/BackgroundWorkerManagerTest.php`、`ut/SemaphoreTest.php`、`ut/MessageQueueTest.php`、`ut/SharedMemoryTest.php` — 基类从 `PHPUnit_Framework_TestCase` 改为 `PHPUnit\Framework\TestCase`；`setUp()` / `tearDown()` 添加 `: void` 返回类型
    - _Ref: Requirement 2, AC 2-3_
  - [x] 2.3 确认 `ut/bootstrap.php` autoload 路径正确，无需结构性变更
  - [x] 2.4 Checkpoint: 执行 `vendor/bin/phpunit` 确认全部现有测试通过，commit
    - _Ref: Requirement 2, AC 4_

- [x] 3. PHP 8 现代化 — Semaphore
  - [x] 3.1 为所有属性添加类型声明：`$maxAcquire: int`、`$id: string`、`$key: int`、`$sem: \SysvSemaphore|null`
    - _Ref: Requirement 3, AC 1-3; Requirement 5, AC 2_
  - [x] 3.2 将 `$id`、`$key`、`$maxAcquire` 标记为 `readonly`（构造后不变）
    - _Ref: Requirement 7, AC 1, 3_
  - [x] 3.3 为所有公共/保护方法添加参数类型和返回类型：`acquire(bool $nowait = false): bool`、`initialize(): void`、`release(): void`、`remove(): void`、`getId(): string`、`withLock(callable $callback): mixed`
    - _Ref: Requirement 5, AC 1-4_
  - [x] 3.4 确认不使用 constructor promotion（design 决策：`$key` 依赖 `$id` 计算，为保持风格一致全部在构造器体内赋值）
    - _Ref: Requirement 4, AC 1_
  - [x] 3.5 移除与原生类型声明完全一致且无额外描述的 PHPDoc 注释；保留含描述文本的注释（如 `@param string $id a string identifying the semaphore`）
    - _Ref: Requirement 8, AC 4_
  - [x] 3.6 顺带检查是否存在 `strpos`/`substr`/`switch`/named arguments 适用场景（design 扫描结果：无适用场景）
    - _Ref: Requirement 6, AC 1-3; Requirement 8, AC 1-3_
  - [x] 3.7 Checkpoint: 执行 `vendor/bin/phpunit` 确认测试通过，commit
    - _Ref: Requirement 3, AC 4; Requirement 5, AC 5; Requirement 7, AC 4; Requirement 11, AC 1-2, 5_

- [x] 4. PHP 8 现代化 — MessageQueue
  - [x] 4.1 为所有属性添加类型声明：`$id: string`、`$key: int`、`$sem: Semaphore`、`$messageSizeLimit: int`、`$queue: \SysvMessageQueue|null`
    - _Ref: Requirement 3, AC 1-3; Requirement 5, AC 2_
  - [x] 4.2 将 `$id`、`$key`、`$sem`、`$messageSizeLimit` 标记为 `readonly`（构造后不变）
    - _Ref: Requirement 7, AC 1, 3_
  - [x] 4.3 为所有公共方法添加参数类型和返回类型：`initialize(): void`、`send(mixed $msg, int $type = 1, bool $blocking = true): bool`、`receive(mixed &$receivedMessage, int &$receivedType, int $expectedType = 0, bool $blocking = true): bool`、`remove(): void`
    - _Ref: Requirement 5, AC 1-4_
  - [x] 4.4 确认不使用 constructor promotion（design 决策：`$key` 和 `$sem` 依赖 `$id` 计算）
    - _Ref: Requirement 4, AC 1_
  - [x] 4.5 移除纯类型 PHPDoc 注释
    - _Ref: Requirement 8, AC 4_
  - [x] 4.6 顺带检查 `strpos`/`substr`/`switch`/named arguments 适用场景
    - _Ref: Requirement 6, AC 1-3; Requirement 8, AC 1-3_
  - [x] 4.7 Checkpoint: 执行 `vendor/bin/phpunit` 确认测试通过，commit
    - _Ref: Requirement 3, AC 4; Requirement 5, AC 5; Requirement 7, AC 4; Requirement 11, AC 1-2, 5_

- [x] 5. PHP 8 现代化 — SharedMemory
  - [x] 5.1 为所有属性添加类型声明：`$id: string`、`$key: int`、`$sem: Semaphore`、`$mem: \SysvSharedMemory|null`
    - _Ref: Requirement 3, AC 1-3; Requirement 5, AC 2_
  - [x] 5.2 将 `$id`、`$key`、`$sem` 标记为 `readonly`（构造后不变）
    - _Ref: Requirement 7, AC 1, 3_
  - [x] 5.3 为所有公共/保护方法添加参数类型和返回类型：`close(): void`、`initialize(): void`、`remove(): void`、`set(string|int $key, mixed $value): bool`、`get(string|int $key): mixed`、`has(string|int $key): bool`、`delete(string|int $key): bool`、`actOnKey(string|int $key, callable $callback): mixed`、`translateKeyToInteger(string|int $key): int`
    - _Ref: Requirement 5, AC 1-4_
  - [x] 5.4 确认不使用 constructor promotion（design 决策：同 Semaphore 理由）
    - _Ref: Requirement 4, AC 1_
  - [x] 5.5 移除纯类型 PHPDoc 注释
    - _Ref: Requirement 8, AC 4_
  - [x] 5.6 顺带检查 `strpos`/`substr`/`switch`/named arguments 适用场景
    - _Ref: Requirement 6, AC 1-3; Requirement 8, AC 1-3_
  - [x] 5.7 Checkpoint: 执行 `vendor/bin/phpunit` 确认测试通过，commit
    - _Ref: Requirement 3, AC 4; Requirement 5, AC 5; Requirement 7, AC 4; Requirement 11, AC 1-2, 5_

- [x] 6. PHP 8 现代化 — WorkerInfo
  - [x] 6.1 为属性添加类型声明：`$id: string`、`$currentWorkerIndex: ?int`、`$totalWorkers: ?int`、`$numberOfConcurrentWorkers: ?int`、`$exitStatus: ?int`；`$worker` 保留无原生类型 + `@var callable` PHPDoc
    - _Ref: Requirement 3, AC 1-2_
  - [x] 6.2 将 `$id` 标记为 `readonly`（构造后不变）；`$worker` 不标记 readonly（无原生类型的属性不能声明 readonly）
    - _Ref: Requirement 7, AC 1, 3_
  - [x] 6.3 为所有 getter 添加返回类型、所有 setter 添加参数类型和 `void` 返回类型
    - _Ref: Requirement 5, AC 1, 3-4_
  - [x] 6.4 确认不使用 constructor promotion（design 决策：`$id` 由计算生成，`$worker` 类型受限）
    - _Ref: Requirement 4, AC 1_
  - [x] 6.5 移除纯类型 PHPDoc 注释；保留 `$worker` 的 `@var callable` 注释
    - _Ref: Requirement 8, AC 4_
  - [x] 6.6 顺带检查 `strpos`/`substr`/`switch`/named arguments 适用场景
    - _Ref: Requirement 6, AC 1-3; Requirement 8, AC 1-3_
  - [x] 6.7 Checkpoint: 执行 `vendor/bin/phpunit` 确认测试通过，commit
    - _Ref: Requirement 3, AC 4; Requirement 5, AC 5; Requirement 11, AC 1-2, 5_

- [x] 7. PHP 8 现代化 — WorkerManagerCompletedEvent
  - [x] 7.1 为属性添加类型声明：`$successfulWorkers: array`、`$failedWorkers: array`
    - _Ref: Requirement 3, AC 1, 3_
  - [x] 7.2 将 `$successfulWorkers`、`$failedWorkers` 标记为 `readonly`（构造后不变）
    - _Ref: Requirement 7, AC 1, 3_
  - [x] 7.3 为构造函数参数添加类型：`__construct(array $successfulWorkers, array $failedWorkers)`；为公共方法添加返回类型：`isSuccessful(): bool`、`getFailedWorkers(): array`、`getSuccessfulWorkers(): array`
    - _Ref: Requirement 5, AC 1, 3-4_
  - [x] 7.4 确认不使用 constructor promotion（design 决策：`parent::__construct()` 调用在构造器体内，为清晰起见不使用 promotion）
    - _Ref: Requirement 4, AC 1_
  - [x] 7.5 保留 `@var WorkerInfo[]` PHPDoc 注释（泛型信息，原生 `array` 无法表达）
    - _Ref: Requirement 8, AC 4_
  - [x] 7.6 顺带检查 `strpos`/`substr`/`switch`/named arguments 适用场景
    - _Ref: Requirement 6, AC 1-3; Requirement 8, AC 1-3_
  - [x] 7.7 Checkpoint: 执行 `vendor/bin/phpunit` 确认测试通过，commit
    - _Ref: Requirement 3, AC 4; Requirement 5, AC 5; Requirement 7, AC 4; Requirement 11, AC 1-2, 5_

- [x] 8. PHP 8 现代化 — BackgroundWorkerManager
  - [x] 8.1 为所有属性添加类型声明：`$parentProcessId: int`、`$numberOfConcurrentWorkers: int`、`$pendingWorkers: array`、`$runningProcesses: array`、`$successfulProcesses: array`、`$failedProcesses: array`、`$startedWorkerCount: int`、`$totalWorkerCount: int`
    - _Ref: Requirement 3, AC 1, 3_
  - [x] 8.2 将 `$parentProcessId` 标记为 `readonly`（构造后不变）；`$numberOfConcurrentWorkers` 不标记 readonly（有公共 setter）
    - _Ref: Requirement 7, AC 1, 3_
  - [x] 8.3 为构造函数参数添加类型：`__construct(int $numberOfConcurrentWorkers = 1)`；为所有公共/保护方法添加参数类型和返回类型：`addWorker(callable $worker, int $count = 1): array`、`run(): int`、`wait(): int`、`hasMoreWork(): bool`、`getNumberOfConcurrentWorkers(): int`、`setNumberOfConcurrentWorkers(int $numberOfConcurrentWorkers): void`、`executeWorker(): void`、`assertInParentProcess(): void`
    - _Ref: Requirement 5, AC 1-4; Requirement 11, AC 3-4_
  - [x] 8.4 确认不使用 constructor promotion（design 决策：`$parentProcessId` 由 `getmypid()` 计算）
    - _Ref: Requirement 4, AC 1_
  - [x] 8.5 保留 `@var WorkerInfo[]` PHPDoc 注释（泛型信息）；移除纯类型注释
    - _Ref: Requirement 8, AC 4_
  - [x] 8.6 顺带检查 `strpos`/`substr`/`switch`/named arguments 适用场景
    - _Ref: Requirement 6, AC 1-3; Requirement 8, AC 1-3_
  - [x] 8.7 Checkpoint: 执行 `vendor/bin/phpunit` 确认全部测试通过（全部 6 个源文件现代化完成），commit
    - _Ref: Requirement 3, AC 4; Requirement 5, AC 5; Requirement 7, AC 4; Requirement 11, AC 1-2, 5_

- [x] 9. PBT — SharedMemory round-trip
  - [x] 9.1 创建 `ut/SharedMemoryPbtTest.php`：继承 `PHPUnit\Framework\TestCase`，`use Eris\TestTrait`；`setUp()` 中创建 `SharedMemory` 实例（使用唯一 ID 确保隔离），调用 `initialize()`；`tearDown()` 中调用 `remove()` 销毁资源
    - _Ref: Requirement 9, AC 1-2_
  - [x] 9.2 编写 Property 1: SharedMemory round-trip 属性测试 — 使用 `forAll(Generator\string(), Generator\oneOf(Generator\int(), Generator\string(), Generator\bool(), Generator\float()))` 生成随机 key-value；验证 `set(key, value)` → `get(key)` === `value`（跳过空 key，每次迭代后 `delete(key)` 清理）
    - _Ref: Requirement 9, AC 2_
  - [x] 9.3 Checkpoint: 执行 `vendor/bin/phpunit --filter SharedMemoryPbtTest` 确认通过，commit

- [x] 10. PBT — MessageQueue round-trip
  - [x] 10.1 创建 `ut/MessageQueuePbtTest.php`：继承 `PHPUnit\Framework\TestCase`，`use Eris\TestTrait`；`setUp()` 中创建 `MessageQueue` 实例（使用唯一 ID），调用 `initialize()`；`tearDown()` 中调用 `remove()` 销毁资源
    - _Ref: Requirement 9, AC 1, 3_
  - [x] 10.2 编写 Property 2: MessageQueue round-trip 属性测试 — 使用 `forAll(Generator\oneOf(Generator\int(), Generator\string(), Generator\bool()))` 生成随机消息；验证 `send(msg)` → `receive()` 返回的消息等于原始 `msg`
    - _Ref: Requirement 9, AC 3_
  - [x] 10.3 Checkpoint: 执行 `vendor/bin/phpunit --filter MessageQueuePbtTest` 确认通过，commit

- [x] 11. PBT — Semaphore idempotence
  - [x] 11.1 创建 `ut/SemaphorePbtTest.php`：继承 `PHPUnit\Framework\TestCase`，`use Eris\TestTrait`；`setUp()` 中创建 `Semaphore` 实例（使用唯一 ID），调用 `initialize()`；`tearDown()` 中调用 `remove()` 销毁资源
    - _Ref: Requirement 9, AC 1, 4_
  - [x] 11.2 编写 Property 3: Semaphore idempotence 属性测试 — 使用 `forAll(Generator\choose(1, 50))` 生成随机循环次数 `n`；验证 `n` 次 acquire/release 循环后，仍可成功 acquire/release
    - _Ref: Requirement 9, AC 4_
  - [x] 11.3 Checkpoint: 执行 `vendor/bin/phpunit` 确认全部测试（现有 + PBT）通过，commit
    - _Ref: Requirement 9, AC 5_

- [-] 12. SSOT 文档更新
  - [x] 12.1 更新 `docs/state/architecture.md`：技术选型表 PHP 版本 `>=5.6.1` → `>=8.2`、测试框架 `PHPUnit ^5.5` → `PHPUnit ^11.0`；新增 `giorgiosironi/eris ^0.14.0` 作为 dev 依赖；测试策略表移除 `enforceTimeLimit` 描述、新增 PBT 测试说明
    - _Ref: Requirement 10, AC 1-3_
  - [x] 12.2 更新 `docs/state/api.md`：反映各类构造函数和方法签名中新增的类型声明（参数类型、返回类型）；反映 readonly 属性变更；确认不记录任何行为变更（本次无行为变更）
    - _Ref: Requirement 10, AC 4-5_
  - [-] 12.3 Checkpoint: 审查 SSOT 文档与代码一致性，commit

- [ ] 13. 手工测试 — Release Stabilize
  - [ ] 13.1 Increment alpha tag
  - [ ] 13.2 验证 `composer install` 在干净环境下无冲突，依赖树正确解析
  - [ ] 13.3 验证 `vendor/bin/phpunit` 全量测试通过，输出干净（无 deprecation warning、无异常堆栈）
  - [ ] 13.4 验证 IPC 组件（Semaphore、MessageQueue、SharedMemory）的 PBT 测试在多次运行下稳定通过
  - [ ] 13.5 验证源代码中所有公共方法签名与 `docs/state/api.md` 一致
  - [ ] 13.6 验证 `docs/state/architecture.md` 中技术选型信息与 `composer.json` 一致
  - [ ] 13.7 Checkpoint: 全部手工测试通过，commit

- [ ] 14. Code Review
  - [ ] 14.1 委托给 code-reviewer sub-agent 执行
  - [ ] 14.2 Checkpoint: Code review 通过，commit

## Issues

（stabilize 阶段新发现的 issue 记录于此，初始为空）

## Socratic Review

**Q: tasks 是否完整覆盖了 design 中的所有实现项？有无遗漏的模块或接口？**
A: Design 中的 6 个层面（Composer 配置、PHPUnit 配置、测试基类适配、源代码 PHP 8 现代化 × 6 文件、PBT 测试 × 3、SSOT 文档更新）均有对应 task。Constructor promotion 在 design 中明确决策为"不使用"，已在每个源文件改造 task 中添加确认步骤。无遗漏。

**Q: task 之间的依赖顺序是否正确？是否存在隐含的前置依赖未体现在排序中？**
A: 依赖链清晰：Composer 依赖（Task 1）→ PHPUnit 配置（Task 2）→ 源代码改造（Task 3-8，IPC 组件先于 Worker 组件因为 Worker 组件依赖 IPC 组件的类型）→ PBT 测试（Task 9-11，依赖源代码改造完成后的类型签名）→ SSOT 文档（Task 12，依赖代码最终状态）→ 手工测试（Task 13）→ Code Review（Task 14）。无隐含依赖。

**Q: 每个 task 的粒度是否合适？是否有过粗或过细的 task？**
A: 源代码改造按文件/组件切分（design CR Q1 决策），每个 task 完成一个类的全部改造，粒度适中。PBT 测试按文件独立（design CR Q4 决策），便于逐个验证。Requirement 6/8 的扫描合并到各文件改造 task 中（design CR Q3 决策）。无过粗或过细。

**Q: checkpoint 的设置是否覆盖了关键阶段？**
A: 每个 top-level task 的最后一个 sub-task 均为 checkpoint，包含具体验证命令和 commit。关键阶段（基础设施就绪、每个文件改造后、每个 PBT 测试后、文档更新后、手工测试后、code review 后）均有 checkpoint 覆盖。

**Q: 手工测试是否覆盖了 requirements 中的关键用户场景？**
A: 手工测试覆盖了：依赖解析正确性（Req 1）、全量测试通过且输出干净（Req 2-9）、PBT 稳定性（Req 9）、文档与代码一致性（Req 10）。公共 API 行为不变（Req 11）通过自动化测试覆盖。

**Q: Design CR 决策是否全部在 tasks 编排中体现？**
A: Q1（按文件/组件切分）→ Task 3-8 各自对应一个源文件；Q2（`<directory>ut</directory>`）→ Task 2.1；Q3（Req 6/8 扫描合并到文件改造 task）→ 各 task 的 sub-task X.6；Q4（PBT 独立 task）→ Task 9-11 各自独立。全部体现。

## Notes

- 按 `spec-execution.md` 规范执行，top-level task 按序号逐项完成
- commit 随 checkpoint 一起执行，每个 top-level task 的最后一个 sub-task 为 checkpoint，通过后进行一次 commit
- 每个源文件改造 task 包含该文件的全部 PHP 8 现代化内容（typed properties + return types + readonly + constructor promotion 确认 + PHPDoc 清理 + Req 6/8 扫描），改完即可运行测试验证
- 每个 PBT 测试文件独立 task，便于逐个验证
- Property tests 验证 design 中定义的 Correctness Properties
- 公共 API 行为保持不变（Requirement 11），所有改造仅涉及类型签名和语法形式
- 本次升级不涉及数据模型变更或外部系统交互，无数据兼容性风险

## Gatekeep Log

**校验时间**: 2025-01-20
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] Checkpoint（原 Task 3、10、14、16）作为独立 top-level task 存在，违反"checkpoint 应为每个 top-level task 的最后一个 sub-task"规则。已将 checkpoint 合并为各 top-level task 的最后一个 sub-task，消除独立 checkpoint task
- [结构] 缺少手工测试 top-level task。Release spec 需要 stabilize 测试阶段。已新增 Task 13（手工测试 — Release Stabilize），包含 alpha tag increment、依赖验证、全量测试、PBT 稳定性、文档一致性等测试项
- [结构] 缺少 Code Review top-level task。已新增 Task 14（Code Review），委托给 code-reviewer sub-agent 执行
- [结构] 缺少 `## Issues` section（release spec 必需）。已补充空的 Issues section
- [结构] 缺少 `## Socratic Review` section。已补充完整的自问自答式审查，覆盖 design 覆盖度、依赖顺序、粒度、checkpoint、手工测试、Design CR 决策
- [格式] Task 1 的 sub-task 为纯列表项，缺少 `- [ ]` checkbox 和层级序号。已修正为标准 checkbox + 序号格式
- [格式] Task 4-9（原编号）的 sub-task 为纯列表项，缺少 `- [ ]` checkbox 和层级序号。已修正为标准 checkbox + 序号格式
- [格式] Task 11.2、12.2、13.2 使用 `[ ]*` 非标准 checkbox 语法标记为 optional。已移除 `*` 标记，所有 task 均为 mandatory
- [内容] Notes 中 "Tasks marked with `*` are optional and can be skipped for faster MVP" 违反"所有 task 均为 mandatory"规则。已移除该条
- [内容] Notes 缺少对 `spec-execution.md` 的引用。已补充
- [内容] Notes 缺少 commit 时机说明。已补充"commit 随 checkpoint 一起执行"
- [内容] Requirement 4（Constructor Promotion）未被任何 task 引用——design 中明确决策为"不使用"，但 tasks 中缺少对应的确认步骤。已在每个源文件改造 task 中添加 constructor promotion 确认 sub-task（如 3.4、4.4 等），引用 Requirement 4, AC 1
- [内容] Requirement 追溯引用格式不规范——原文使用 `_Requirements: 1.1, 1.2_` 格式，应为 `_Ref: Requirement X, AC Y_` 格式。已统一修正
- [内容] Requirement 9 AC 5（全部测试含 PBT 通过）和 Requirement 11 AC 5（现有测试通过确认行为等价）未被显式引用。已在 Task 11.3 checkpoint 和各源文件改造 checkpoint 中补充引用

### 合规检查
- [x] 无 TBD / TODO / 占位符
- [x] 无空 section 或不完整列表
- [x] 内部引用一致（requirement 编号、design 模块名）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] Release spec 手工测试类 top-level task 的第一个 sub-task 是 "Increment alpha tag"
- [x] 最后一个 top-level task 是 Code Review
- [x] 倒数第二个 top-level task 是手工测试
- [x] 自动化实现 task 排在手工测试和 Code Review 之前
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号（1-14）
- [x] sub-task 有层级序号（X.1, X.2...）
- [x] 序号连续，无跳号
- [x] 每个实现类 sub-task 引用了具体的 requirements 条款
- [x] requirements.md 中的每条 requirement（1-11）至少被一个 task 引用
- [x] 引用的 requirement 编号和 AC 编号在 requirements.md 中确实存在
- [x] top-level task 按依赖关系排序
- [x] 无循环依赖
- [x] checkpoint 为每个 top-level task 的最后一个 sub-task
- [x] checkpoint 包含具体验证命令和 commit 动作
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory
- [x] 手工测试 top-level task 存在
- [x] 手工测试覆盖关键用户场景
- [x] Code Review 是最后一个 top-level task
- [x] Code Review 描述为委托给 code-reviewer sub-agent 执行
- [x] Code Review task 未展开 review checklist
- [x] `## Notes` section 存在
- [x] Notes 引用了 `spec-execution.md`
- [x] Notes 说明 commit 随 checkpoint 执行
- [x] `## Socratic Review` section 存在且覆盖充分
- [x] `## Issues` section 存在（release spec）
- [x] Design CR 决策（Q1-Q4）全部在 tasks 编排中体现
- [x] Design 全覆盖（11 条 requirement 均有对应 task）
- [x] 每个 sub-task 可独立执行
- [x] 验收闭环完整（checkpoint + 手工测试 + code review）
- [x] 执行路径无歧义
