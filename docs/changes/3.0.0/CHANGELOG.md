# Changelog v3.0.0

本文件记录 v3.0.0 release 的变更内容。

---

## 目标摘要

- 将运行时依赖 **`oasis/event`**、**`oasis/logging`** 的 Composer 约束升至 **`^3.0`**，并与 `composer.lock` 对齐（当前锁至 **`v3.0.0`**，传递依赖含 **`oasis/utils ^3.0`**、**`monolog/monolog` 3.x** 等）。
- 在 `src/`、`ut/` 上继续做 PHP 8.2 惯用类型与测试风格收敛（不引入 PHP 8.3+ 专属语言特性；本库未采用 `strict_types=1`）。
- SSOT 与 `PROJECT.md` 在锁文件与代码稳定后更新。

---

## 依赖与传递升级

| 包 | 约束 | 说明 |
|----|------|------|
| `oasis/event` | `^3.0` | 事件基类与分发契约以 3.x 为准 |
| `oasis/logging` | `^3.0` | 经 Monolog 3 线；`ut/bootstrap` 使用 `LocalFileHandler` 安装 |
| `oasis/utils` | （传递）`^3.0` | 由 `oasis/logging` 引入 |

---

## 代码与行为

- **`WorkerInfo`**：内部以 **`Closure::fromCallable`** 存储 worker，属性类型为只读 **`\Closure`**，对外 **`getWorker()`** 仍返回可调用值；子进程执行处改为 **`$worker($info)`**。
- **System V 初始化**（与 PHP 8 类型化资源属性配合）  
  - **`Semaphore::initialize()`**：`sem_get` 使用 **`auto_release = true`**；失败抛 **`RuntimeException`**。  
  - **`MessageQueue::initialize()`** / **`SharedMemory::initialize()`**：`msg_get_queue` / `shm_attach` 失败时抛 **`RuntimeException`**，避免将 **`false`** 赋给 `?\Sysv*` 属性。

---

## 测试

- PHPUnit 全量；若干集成测试用例补充/统一 **`void` 返回类型** 与 **typed 测试类属性**。

---

## 回滚参考

- 将 **`composer.json`** / **`composer.lock`** 回退到升级前版本并 **`composer install`**。  
- 将 **`src/`**、**`ut/`** 中与上述变更对应的文件按合并前状态恢复。  
- 本版本未引入本库对外 **公共方法** 的删改，主要风险在 **上游 3.x 的传递依赖** 与 **运行环境 System V / PCNTL**；若回滚后事件或日志层仍不兼容，请核对下游对 **`oasis/event` / `oasis/logging` 2.x 与 3.x** 的约束。
