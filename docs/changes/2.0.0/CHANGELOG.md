# Changelog v2.0.0

本文件记录 v2.0.0 release 的变更内容。

---

## 包含的 Feature

### PHP 8 升级（release-2.0.0）

**基础设施**

- `composer.json` PHP 最低版本从 `>=5.6.1` 提升至 `>=8.2`
- PHPUnit 从 `^5.5` 升级至 `^11.0`，`phpunit.xml` 适配 PHPUnit 11 schema
- 新增 dev 依赖 `giorgiosironi/eris ^1.0`（Property-Based Testing）

**源代码 PHP 8 现代化**

- 全部 6 个源文件添加 typed properties（含 PHP 8 原生 SysV 对象类型：`\SysvSemaphore`、`\SysvMessageQueue`、`\SysvSharedMemory`）
- 全部公共/保护方法添加参数类型和返回类型声明
- 构造后不变的属性标记为 `readonly`（`Semaphore.$id/$key/$maxAcquire`、`MessageQueue.$id/$key/$sem/$messageSizeLimit`、`SharedMemory.$id/$key/$sem`、`WorkerInfo.$id`、`WorkerManagerCompletedEvent.$successfulWorkers/$failedWorkers`、`BackgroundWorkerManager.$parentProcessId`）
- 移除与原生类型声明完全一致且无额外描述的 PHPDoc 注释；保留含描述文本和泛型信息的注释

**测试**

- 4 个现有测试文件适配 PHPUnit 11（基类、`setUp()`/`tearDown()` 返回类型）
- 新增 3 个 Property-Based Test：SharedMemory round-trip、MessageQueue round-trip、Semaphore idempotence

**公共 API**

- 方法签名添加了类型声明，但行为语义不变
- 不新增、不删除、不重命名任何公共方法
- 事件常量和分发语义不变

---

## 修复的 Issue

### Stabilize 阶段发现（v2.0.0-alpha1）

1. **eris ^0.14.0 在 PHP 8.5 上产生 deprecation warning** — eris 0.14.x 内部使用 implicit nullable parameter，PHP 8.5 标记为 deprecated。升级 eris 至 `^1.0`（实际安装 1.1.0）解决。同步更新 `composer.json`、`docs/state/architecture.md`。
2. **testSerialization 依赖 ext-memcached** — `MessageQueueTest::testSerialization` 和 `SharedMemoryTest::testSerialization` 使用 `new Memcached()` 作为序列化测试对象，但 `ext-memcached` 不是项目依赖。改用 `stdClass` 替代。
3. **两个 risky test 缺少断言** — `SemaphoreTest::testNormalCase` 和 `MessageQueueTest::testNonBlockingReceive` 无断言，PHPUnit 11 标记为 risky。补充断言修复。

---

## 工程变更

- `phpunit.xml` testsuite 从逐文件 `<file>` 列举改为 `<directory>ut</directory>`
- 移除 `phpunit.xml` 中已废弃的 `enforceTimeLimit` 属性

---

## 测试覆盖

- 25 tests, 2989 assertions（含 3 个 PBT 测试，每个默认 100 次迭代）
- 全量测试通过，输出干净（无 deprecation warning、无 risky test）
