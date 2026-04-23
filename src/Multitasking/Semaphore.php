<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-31
 * Time: 20:09
 */

namespace Oasis\Mlib\Multitasking;

class Semaphore
{

    protected readonly int $maxAcquire;
    protected readonly string $id;
    protected readonly int $key;
    protected \SysvSemaphore|null $sem = null;

    /**
     * Semaphore constructor.
     *
     * @param string $id a string identifying the semaphore
     */
    public function __construct(string $id, int $maxAcquire = 1)
    {
        $this->id         = $id;
        $this->key        = hexdec(substr(md5(md5($id) . 'oasis-semaphore'), 0, 8));
        $this->maxAcquire = $maxAcquire;
    }

    function __destruct()
    {
    }

    public function acquire(bool $nowait = false): bool
    {
        if (!$this->sem) {
            $this->initialize();
        }

        $this->debug("Acquiring semaphore for %s, key = %x", $this->id, $this->key);
        $ret = sem_acquire($this->sem, $nowait);
        if ($ret) {
            $this->debug("Acquired");
        }

        return $ret;
    }

    public function initialize(): void
    {
        $this->debug("Initializing semaphore %s with key: %x", $this->id, $this->key);
        $this->sem = sem_get($this->key, $this->maxAcquire, 0666, 1);
    }

    public function release(): void
    {
        if (!$this->sem) {
            mwarning("Releasing while not initialized!");

            return;
        }

        $this->debug("Releasing semaphore for %s, key = %x", $this->id, $this->key);
        sem_release($this->sem);
        $this->debug("Released");
    }

    public function remove(): void
    {
        if (!$this->sem) {
            $this->initialize();
        }

        $this->debug("Removing semaphore for %s, key: %x", $this->id, $this->key);
        sem_remove($this->sem);
        $this->sem = null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function withLock(callable $callback): mixed
    {
        $this->acquire();
        try {
            $ret = $callback();
        } finally {
            $this->release();
        }

        return $ret;
    }

    private function debug(string $format, mixed ...$args): void
    {
        static $logDebug = null;
        if ($logDebug === null && \getenv('DEBUG_OASIS_MULTITASKING')) {
            $logDebug = true;
        } else {
            $logDebug = false;
        }
        if ($logDebug) {
            \call_user_func_array("mdebug", [$format, ...$args]);
        }
    }
}
