<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-01
 * Time: 20:55
 */

namespace Oasis\Mlib\Multitasking;

class SharedMemory
{
    protected $id;
    protected $key;
    protected $sem;

    protected $mem;

    /**
     * SharedMemory constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id  = $id;
        $this->key = hexdec(substr(md5(md5($id) . "oasis-shared-memory"), 0, 8));
        $this->sem = new Semaphore(__CLASS__ . "#" . $id);
    }

    public function close()
    {
        if ($this->mem) {
            $this->sem->acquire();
            try {
                shm_detach($this->mem);
                $this->mem = null;
            } finally {
                $this->sem->release();
            }
        }
    }

    public function initialize()
    {
        if (!$this->mem) {
            $this->mem = shm_attach($this->key);
        }
    }

    public function remove()
    {
        $this->initialize();
        mdebug("Removing shared memory, key = %s", $this->key);

        $this->sem->acquire();
        try {
            shm_remove($this->mem);
            $this->mem = null;
        } finally {
            $this->sem->release();
            $this->sem->remove();
        }

    }

    public function set($key, $value)
    {
        $this->initialize();
        return $this->sem->withLock(function () use ($key, $value) {
            $key = $this->translateKeyToInteger($key);
            return shm_put_var($this->mem, $key, $value);
        });
    }

    public function get($key)
    {
        $this->initialize();

        return $this->sem->withLock(function () use ($key) {
            $key = $this->translateKeyToInteger($key);
            if (shm_has_var($this->mem, $key)) {
                $ret = shm_get_var($this->mem, $key);
            } else {
                $ret = null;
            }

            return $ret;
        });
    }

    public function has($key)
    {
        $this->initialize();

        return $this->sem->withLock(function () use ($key) {
            $key = $this->translateKeyToInteger($key);
            return shm_has_var($this->mem, $key);
        });
    }

    public function delete($key)
    {
        $this->initialize();

        return $this->sem->withLock(function () use ($key) {
            $key = $this->translateKeyToInteger($key);
            return shm_remove_var($this->mem, $key);
        });
    }

    public function actOnKey($key, callable $callback)
    {
        $this->initialize();

        return $this->sem->withLock(
            function () use ($key, $callback) {
                $key = $this->translateKeyToInteger($key);
                if (shm_has_var($this->mem, $key)) {
                    $ret = shm_get_var($this->mem, $key);
                } else {
                    $ret = null;
                }
                $ret = $callback($ret);
                shm_put_var($this->mem, $key, $ret);

                return $ret;
            }
        );
    }

    protected function translateKeyToInteger($key)
    {
        return hexdec(substr(md5($key), 0, 8));
    }

}
