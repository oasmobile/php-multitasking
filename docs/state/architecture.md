# Architecture

系统架构与工程约束的 SSOT。

---

## 技术选型

| 项目 | 值 |
|------|----|
| 语言 | PHP ≥ 8.2 |
| 包名 | `oasis/multitasking` |
| 命名空间 | `Oasis\Mlib\Multitasking` |
| 自动加载 | PSR-4（`src/` → `Oasis\Mlib\`） |
| 进程管理 | `ext-pcntl`（`pcntl_fork` / `pcntl_waitpid`） |
| IPC 原语 | System V（`sem_*` / `msg_*` / `shm_*`） |
| 事件机制 | `oasis/event ^1.0`（`EventDispatcherInterface` / `EventDispatcherTrait`） |
| 日志 | `oasis/logging ^1.1`（全局函数 `mdebug` / `minfo` / `mnotice` / `mwarning` / `merror`） |
| 测试 | PHPUnit ^11.0 |
| PBT | `giorgiosironi/eris ^0.14.0`（dev 依赖） |

---

## 模块结构

```
src/Multitasking/
├── BackgroundWorkerManager.php   # 后台 worker 管理器（核心）
├── WorkerInfo.php                # worker 元信息
├── WorkerManagerCompletedEvent.php # 全部 worker 完成事件
├── Semaphore.php                 # 信号量封装
├── MessageQueue.php              # 消息队列封装
└── SharedMemory.php              # 共享内存封装
```

---

## 组件依赖关系

```
BackgroundWorkerManager
  ├── WorkerInfo（组合）
  ├── WorkerManagerCompletedEvent（事件载体）
  └── EventDispatcherTrait（事件分发）

MessageQueue
  └── Semaphore（并发保护）

SharedMemory
  └── Semaphore（并发保护）
```

---

## IPC Key 生成规则

三个 IPC 组件均使用相同模式生成 System V key：

```
key = hexdec(substr(md5(md5($id) . $salt), 0, 8))
```

| 组件 | salt |
|------|------|
| `Semaphore` | `oasis-semaphore` |
| `MessageQueue` | `oasis-msg-queue` |
| `SharedMemory` | `oasis-shared-memory` |

---

## 测试策略

| 项目 | 值 |
|------|----|
| 框架 | PHPUnit ^11.0 |
| 配置 | `phpunit.xml` |
| 引导 | `ut/bootstrap.php` |
| 测试目录 | `ut/` |
| PBT 框架 | `giorgiosironi/eris ^0.14.0` |
| PBT 覆盖 | IPC 组件（SharedMemory round-trip、MessageQueue round-trip、Semaphore idempotence） |
