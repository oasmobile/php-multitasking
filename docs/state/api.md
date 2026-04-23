# API

公共类与接口的 SSOT。

---

## BackgroundWorkerManager

后台 worker 管理器，通过 `pcntl_fork` 在子进程中执行 callable。

实现 `EventDispatcherInterface`。

### 属性

| 属性 | 类型 | Readonly | 说明 |
|------|------|----------|------|
| `$parentProcessId` | `int` | ✓ | 父进程 PID |
| `$numberOfConcurrentWorkers` | `int` | | 并发 worker 数 |

### 构造

```php
__construct(int $numberOfConcurrentWorkers = 1)
```

### 公共方法

| 方法 | 签名 | 说明 |
|------|------|------|
| `addWorker` | `addWorker(callable $worker, int $count = 1): array` | 添加 worker，返回 ID 数组 |
| `run` | `run(): int` | 启动执行，返回已启动 worker 数 |
| `wait` | `wait(): int` | 阻塞等待全部完成，返回失败 worker 数 |
| `hasMoreWork` | `hasMoreWork(): bool` | 是否还有待执行 worker |
| `getNumberOfConcurrentWorkers` | `getNumberOfConcurrentWorkers(): int` | 获取并发数 |
| `setNumberOfConcurrentWorkers` | `setNumberOfConcurrentWorkers(int $numberOfConcurrentWorkers): void` | 设置并发数 |

### 事件

| 常量 | 值 | 触发时机 | 载荷 |
|------|----|----------|------|
| `EVENT_WORKER_FINISHED` | `worker_finished` | 单个 worker 退出 | `WorkerInfo` |
| `EVENT_ALL_COMPLETED` | `all_completed` | 全部 worker 完成 | `WorkerManagerCompletedEvent` |

### 约束

- `run()` / `wait()` / `executeWorker()` 只能在父进程调用，子进程调用抛 `RuntimeException`
- `run()` 重复调用（仍有 running worker 时）抛 `RuntimeException`

---

## WorkerInfo

worker 的元信息容器，由 `BackgroundWorkerManager` 创建并传入 worker callable。

### 属性

| 属性 | 类型 | Readonly | 说明 |
|------|------|----------|------|
| `$id` | `string` | ✓ | 唯一 ID（md5 生成） |
| `$worker` | _(无原生类型, `@var callable`)_ | | 原始 callable |

### 公共方法

| 方法 | 返回类型 | 说明 |
|------|----------|------|
| `getId()` | `string` | 唯一 ID（md5 生成） |
| `getWorker()` | `callable` | 原始 callable |
| `getCurrentWorkerIndex()` | `?int` | 当前 worker 序号（0-based） |
| `setCurrentWorkerIndex(int $currentWorkerIndex)` | `void` | 设置 worker 序号 |
| `getTotalWorkers()` | `?int` | worker 总数 |
| `setTotalWorkers(int $totalWorkers)` | `void` | 设置 worker 总数 |
| `getNumberOfConcurrentWorkers()` | `?int` | 并发数 |
| `setNumberOfConcurrentWorkers(int $numberOfConcurrentWorkers)` | `void` | 设置并发数 |
| `getExitStatus()` | `?int` | 子进程退出码（`wait()` 后可用） |
| `setExitStatus(int $exitStatus)` | `void` | 设置退出码 |

---

## WorkerManagerCompletedEvent

`EVENT_ALL_COMPLETED` 事件的载体，继承 `Oasis\Mlib\Event\Event`。

### 属性

| 属性 | 类型 | Readonly | 说明 |
|------|------|----------|------|
| `$successfulWorkers` | `array` (`WorkerInfo[]`) | ✓ | 成功的 worker 列表 |
| `$failedWorkers` | `array` (`WorkerInfo[]`) | ✓ | 失败的 worker 列表 |

### 构造

```php
__construct(array $successfulWorkers, array $failedWorkers)
```

### 公共方法

| 方法 | 返回类型 | 说明 |
|------|----------|------|
| `isSuccessful()` | `bool` | 是否全部成功（无失败 worker） |
| `getSuccessfulWorkers()` | `WorkerInfo[]` | 成功的 worker 列表 |
| `getFailedWorkers()` | `WorkerInfo[]` | 失败的 worker 列表 |

---

## Semaphore

System V 信号量的封装。

### 属性

| 属性 | 类型 | Readonly | 说明 |
|------|------|----------|------|
| `$id` | `string` | ✓ | 标识符 |
| `$key` | `int` | ✓ | System V key |
| `$maxAcquire` | `int` | ✓ | 最大并发获取数 |

### 构造

```php
__construct(string $id, int $maxAcquire = 1)
```

### 公共方法

| 方法 | 签名 | 说明 |
|------|------|------|
| `initialize` | `initialize(): void` | 初始化信号量资源 |
| `acquire` | `acquire(bool $nowait = false): bool` | 获取锁 |
| `release` | `release(): void` | 释放锁 |
| `remove` | `remove(): void` | 移除信号量 |
| `getId` | `getId(): string` | 获取 ID |
| `withLock` | `withLock(callable $callback): mixed` | 在锁内执行回调 |

### 调试

设置环境变量 `DEBUG_OASIS_MULTITASKING` 可开启调试日志。

---

## MessageQueue

System V 消息队列的封装。

### 属性

| 属性 | 类型 | Readonly | 说明 |
|------|------|----------|------|
| `$id` | `string` | ✓ | 标识符 |
| `$key` | `int` | ✓ | System V key |
| `$sem` | `Semaphore` | ✓ | 并发保护信号量 |
| `$messageSizeLimit` | `int` | ✓ | 消息大小限制 |

### 构造

```php
__construct(string $id, int $messageSizeLimit = 2048)
```

### 公共方法

| 方法 | 签名 | 说明 |
|------|------|------|
| `initialize` | `initialize(): void` | 创建 / 获取队列资源 |
| `send` | `send(mixed $msg, int $type = 1, bool $blocking = true): bool` | 发送消息 |
| `receive` | `receive(mixed &$receivedMessage, mixed &$receivedType, int $expectedType = 0, bool $blocking = true): bool` | 接收消息 |
| `remove` | `remove(): void` | 移除队列及关联信号量 |

### 约束

- `$type` 必须为正整数，否则抛 `InvalidArgumentException`
- 非阻塞模式下通过内部 `Semaphore` 保护并发访问

---

## SharedMemory

System V 共享内存的封装，key-value 语义。

### 属性

| 属性 | 类型 | Readonly | 说明 |
|------|------|----------|------|
| `$id` | `string` | ✓ | 标识符 |
| `$key` | `int` | ✓ | System V key |
| `$sem` | `Semaphore` | ✓ | 并发保护信号量 |

### 构造

```php
__construct(string $id)
```

### 公共方法

| 方法 | 签名 | 说明 |
|------|------|------|
| `initialize` | `initialize(): void` | 附加共享内存段 |
| `close` | `close(): void` | 分离共享内存段 |
| `remove` | `remove(): void` | 移除共享内存及关联信号量 |
| `set` | `set(string\|int $key, mixed $value): bool` | 写入 |
| `get` | `get(string\|int $key): mixed` | 读取（不存在返回 `null`） |
| `has` | `has(string\|int $key): bool` | 是否存在 |
| `delete` | `delete(string\|int $key): bool` | 删除 |
| `actOnKey` | `actOnKey(string\|int $key, callable $callback): mixed` | 原子读-改-写 |

### 约束

- 所有读写操作通过内部 `Semaphore` 保护并发访问
- key 通过 `md5` 转换为整数供 `shm_*` 函数使用
