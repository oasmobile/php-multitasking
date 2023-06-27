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

    protected $maxAcquire = 1;
    protected $id         = '';
    protected $key        = '';
    protected $sem        = null;

    /**
     * Semaphore constructor.
     *
     * @param string $id a string identifying the semaphore
     * @param int    $maxAcquire
     */
    public function __construct($id, $maxAcquire = 1)
    {
        $this->id         = $id;
        $this->key        = hexdec(substr(md5(md5($id) . 'oasis-semaphore'), 0, 8));
        $this->maxAcquire = $maxAcquire;
    }

    function __destruct()
    {
    }

    public function acquire($nowait = false)
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

    public function initialize()
    {
        $this->debug("Initializing semaphore %s with key: %x", $this->id, $this->key);
        $this->sem = sem_get($this->key, $this->maxAcquire, 0666, 1);
    }

    public function release()
    {
        if (!$this->sem) {
            mwarning("Releasing while not initialized!");

            return;
        }

        $this->debug("Releasing semaphore for %s, key = %x", $this->id, $this->key);
        sem_release($this->sem);
        $this->debug("Released");
    }

    public function remove()
    {
        if (!$this->sem) {
            $this->initialize();
        }

        $this->debug("Removing semaphore for %s, key: %x", $this->id, $this->key);
        sem_remove($this->sem);
        $this->sem = null;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function withLock($callback)
    {
        $this->acquire();
        try {
            $ret = $callback();
        } finally {
            $this->release();
        }

        return $ret;
    }

    private function debug(...$args)
    {
        static $logDebug = null;
        if ($logDebug === null && \getenv('DEBUG_OASIS_MULTITASKING')) {
            $logDebug = true;
        } else {
            $logDebug = false;
        }
        if ($logDebug) {
            \call_user_func_array("mdebug", $args);
        }
    }
}
