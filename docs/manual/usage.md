# Usage

`oasis/multitasking` 的使用说明。

---

## 安装

```bash
composer require oasis/multitasking
```

需要 PHP ≥ 8.2 及 `ext-pcntl` 扩展（仅 Linux / macOS）。

---

## BackgroundWorkerManager

在后台子进程中并发执行多个 callable。

### 基本用法

```php
use Oasis\Mlib\Multitasking\BackgroundWorkerManager;
use Oasis\Mlib\Multitasking\WorkerInfo;

$manager = new BackgroundWorkerManager(2); // 最多 2 个并发

// 添加 10 个 worker
$manager->addWorker(
    function (WorkerInfo $info) {
        echo sprintf(
            "Worker #%d / %d\n",
            $info->getCurrentWorkerIndex(),
            $info->getTotalWorkers()
        );
    },
    10
);

$manager->run();
$failed = $manager->wait(); // 阻塞直到全部完成

if ($failed > 0) {
    echo "$failed workers failed.\n";
}
```

### 监听事件

```php
use Oasis\Mlib\Multitasking\WorkerManagerCompletedEvent;

// 单个 worker 完成
$manager->addEventListener(
    BackgroundWorkerManager::EVENT_WORKER_FINISHED,
    function (WorkerInfo $info) {
        echo "Worker {$info->getId()} exited with {$info->getExitStatus()}\n";
    }
);

// 全部完成
$manager->addEventListener(
    BackgroundWorkerManager::EVENT_ALL_COMPLETED,
    function (WorkerManagerCompletedEvent $event) {
        if ($event->isSuccessful()) {
            echo "All workers succeeded.\n";
        }
    }
);
```

### Worker 返回退出码

worker callable 返回整数值作为子进程退出码：

```php
$manager->addWorker(function (WorkerInfo $info) {
    // 返回 0 表示成功，非 0 表示失败
    return doSomeWork() ? 0 : 1;
});
```

---

## Semaphore

进程间互斥锁。

```php
use Oasis\Mlib\Multitasking\Semaphore;

$sem = new Semaphore('my-lock');

// 方式一：手动 acquire / release
$sem->acquire();
try {
    // 临界区
} finally {
    $sem->release();
}

// 方式二：withLock
$result = $sem->withLock(function () {
    // 临界区
    return 'done';
});

// 清理
$sem->remove();
```

---

## MessageQueue

进程间消息传递。

```php
use Oasis\Mlib\Multitasking\MessageQueue;

$queue = new MessageQueue('my-queue');

// 发送
$queue->send('hello', 1);           // type=1, 阻塞
$queue->send('world', 2, false);    // type=2, 非阻塞

// 接收
$queue->receive($msg, $type);                  // 阻塞，接收任意类型
$queue->receive($msg, $type, 1, false);        // 非阻塞，只接收 type=1

// 清理
$queue->remove();
```

- `$type` 必须为正整数
- 默认消息大小限制 2048 字节，可在构造时调整

---

## SharedMemory

进程间共享的 key-value 存储。

```php
use Oasis\Mlib\Multitasking\SharedMemory;

$shm = new SharedMemory('my-shm');

$shm->set('counter', 0);
$shm->get('counter');       // 0
$shm->has('counter');       // true

// 原子读-改-写
$shm->actOnKey('counter', function ($current) {
    return $current + 1;
});

$shm->delete('counter');

// 分离 / 清理
$shm->close();
$shm->remove();
```

---

## 调试

设置环境变量开启 `Semaphore` 调试日志：

```bash
DEBUG_OASIS_MULTITASKING=1 php your-script.php
```
