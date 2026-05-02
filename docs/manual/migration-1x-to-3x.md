# Migration Guide: 1.x → ^3.0

从 `oasis/multitasking` 1.x 升级到 ^3.0 的迁移指南。本文档覆盖两个 major 版本的累积变更（1.x → 2.0 → 3.0），帮助一次性完成升级。

---

## 环境要求变更

| 项目 | 1.x | ^3.0 |
|------|-----|------|
| PHP | ≥ 5.6.1 | ≥ 8.2 |
| `ext-pcntl` | 必需 | 必需（不变） |
| `oasis/event` | ^1.0 | ^3.0 |
| `oasis/logging` | ^1.0 | ^3.0（经 Monolog 3） |
| PHPUnit（dev） | ^5.5 | ^11.0 |

升级前请确认运行环境满足 PHP ≥ 8.2，并且 `oasis/event` ^3.0 和 `oasis/logging` ^3.0 可从 Packagist 解析。

---

## 升级步骤

### 1. 更新 Composer 约束

```bash
composer require oasis/multitasking:^3.0
```

如果项目同时直接依赖 `oasis/event` 或 `oasis/logging`，需一并升级：

```bash
composer require oasis/event:^3.0 oasis/logging:^3.0
```

若出现依赖冲突，使用 `-W` 允许传递依赖同步升级：

```bash
composer update -W oasis/multitasking oasis/event oasis/logging
```

### 2. 适配代码变更

按下文「Breaking Changes」和「行为变更」逐项检查并修改调用方代码。

### 3. 运行测试

```bash
vendor/bin/phpunit
```

---

## Breaking Changes

### PHP 版本要求提升至 ≥ 8.2

1.x 支持 PHP 5.6+，3.0 要求 PHP ≥ 8.2。这是最主要的 breaking change。

### 方法签名添加了原生类型声明

所有公共方法的参数和返回值均添加了 PHP 原生类型声明。如果调用方传入了不匹配的类型，PHP 8 会抛出 `TypeError`（1.x 下会静默类型转换）。

**影响范围**：所有类的公共方法。以下列出需要关注的典型签名：

```php
// BackgroundWorkerManager
__construct(int $numberOfConcurrentWorkers = 1)
addWorker(callable $worker, int $count = 1): array
run(): int
wait(): int
setNumberOfConcurrentWorkers(int $numberOfConcurrentWorkers): void

// Semaphore
__construct(string $id, int $maxAcquire = 1)
acquire(bool $nowait = false): bool
initialize(): void
release(): void
withLock(callable $callback): mixed

// MessageQueue
__construct(string $id, int $messageSizeLimit = 2048)
send(mixed $msg, int $type = 1, bool $blocking = true): bool
receive(mixed &$receivedMessage, mixed &$receivedType, int $expectedType = 0, bool $blocking = true): bool

// SharedMemory
set(string|int $key, mixed $value): bool
get(string|int $key): mixed
has(string|int $key): bool
delete(string|int $key): bool
actOnKey(string|int $key, callable $callback): mixed
```

**迁移动作**：检查调用点，确保传入参数类型与声明一致。常见问题：

- 向 `$type` 参数传入字符串数字（如 `"1"`）→ 改为传入整数 `1`
- 向 `$count` 参数传入浮点数 → 改为传入整数
- 向 `$id` 参数传入非字符串值 → 改为传入字符串

### 属性添加了 `readonly` 修饰

以下属性在 3.0 中为 `readonly`，不可在构造后修改：

| 类 | Readonly 属性 |
|----|---------------|
| `Semaphore` | `$id`, `$key`, `$maxAcquire` |
| `MessageQueue` | `$id`, `$key`, `$sem`, `$messageSizeLimit` |
| `SharedMemory` | `$id`, `$key`, `$sem` |
| `WorkerInfo` | `$id`, `$worker` |
| `WorkerManagerCompletedEvent` | `$successfulWorkers`, `$failedWorkers` |
| `BackgroundWorkerManager` | `$parentProcessId` |

**迁移动作**：如果子类或外部代码直接写入了这些属性（而非通过 setter），需要改为在构造时传入。

> `BackgroundWorkerManager::$numberOfConcurrentWorkers` 不是 readonly，仍可通过 `setNumberOfConcurrentWorkers()` 修改。

### `oasis/event` ^3.0 与 `oasis/logging` ^3.0

上游依赖的 major 版本升级。如果项目中直接使用了 `oasis/event` 或 `oasis/logging` 的 API，需参考各自的升级说明。

关键影响：

- 事件基类与 dispatcher 契约以 `oasis/event` 3.x 为准
- 日志经 Monolog 3 线；全局函数 `mdebug` / `minfo` / `mnotice` / `mwarning` / `merror` 仍可用

---

## 行为变更

### `WorkerInfo::$worker` 内部存储为 `\Closure`

3.0 中 `WorkerInfo` 内部使用 `Closure::fromCallable()` 将传入的 callable 转为 `\Closure` 存储，属性类型为 `readonly \Closure`。

对外 `getWorker()` 仍返回 `callable`（`\Closure` 实现了 callable 契约），**调用方无需修改**。

### System V 初始化失败时抛出 `RuntimeException`

3.0 中 `Semaphore::initialize()`、`MessageQueue::initialize()`、`SharedMemory::initialize()` 在底层系统调用失败时会抛出 `RuntimeException`，而非返回 `false` 或静默失败。

**迁移动作**：如果调用方依赖初始化失败时的静默行为，需添加 `try/catch`。

### `Semaphore::initialize()` 使用 `auto_release = true`

`sem_get()` 调用时显式传入 `auto_release = true`，确保进程异常退出时自动释放信号量。这与 PHP 默认行为一致，通常无需调整。

---

## 不变的部分

以下内容在 1.x → 3.0 之间保持不变：

- **公共方法集合**：未新增、删除或重命名任何公共方法
- **事件常量**：`EVENT_WORKER_FINISHED`（`worker_finished`）和 `EVENT_ALL_COMPLETED`（`all_completed`）的名称与分发语义不变
- **IPC key 生成规则**：`hexdec(substr(md5(md5($id) . $salt), 0, 8))` 算法与 salt 不变，已有的 System V 资源 key 兼容
- **命名空间**：`Oasis\Mlib\Multitasking` 不变
- **模块结构**：6 个源文件的职责与组合关系不变

---

## 常见问题

### Q: 能否从 1.x 直接升到 3.0，跳过 2.0？

可以。2.0 和 3.0 的公共 API 行为语义一致，区别在于 2.0 使用 `oasis/event ^2.0` + `oasis/logging ^2.0`，3.0 升级到了 ^3.0。直接升到 3.0 即可。

### Q: 升级后已有的 System V 资源（信号量、消息队列、共享内存）是否兼容？

兼容。IPC key 生成算法未变，已有资源可继续使用。

### Q: 需要修改事件监听代码吗？

如果只使用了 `addEventListener` 监听 `EVENT_WORKER_FINISHED` 和 `EVENT_ALL_COMPLETED`，通常无需修改。但如果直接继承了 `oasis/event` 的基类，需确认与 3.x 契约兼容。

### Q: PHP 8 的类型声明会影响现有调用吗？

如果 1.x 下的调用方始终传入了正确类型的参数，升级后不会有问题。PHP 8 的类型声明只是将隐式约束变为显式——传入错误类型时，1.x 下可能静默转换或产生意外行为，3.0 下会直接抛出 `TypeError`。

---

## 版本对照速查

| 维度 | 1.x | 2.0 | 3.0 |
|------|-----|-----|-----|
| PHP | ≥ 5.6.1 | ≥ 8.2 | ≥ 8.2 |
| `oasis/event` | ^1.0 | ^2.0 | ^3.0 |
| `oasis/logging` | ^1.0 | ^2.0 | ^3.0 |
| PHPUnit | ^5.5 | ^11.0 | ^11.0 |
| 类型声明 | 无 | 全量 | 全量 |
| `readonly` 属性 | 无 | 有 | 有 |
| PBT 测试 | 无 | 有（eris） | 有（eris） |
| 公共 API 方法集 | 基线 | 不变 | 不变 |
| 事件语义 | 基线 | 不变 | 不变 |
| IPC key 算法 | 基线 | 不变 | 不变 |
