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
    
    public function acquire()
    {
        if (!$this->sem) {
            $this->initialize();
        }
        
        mdebug("Acquiring semaphore for %x", $this->key);
        sem_acquire($this->sem);
        
        mdebug("Acquired");
    }
    
    public function initialize()
    {
        mdebug("Initializing semaphore with key: %x", $this->key);
        $this->sem = sem_get($this->key, $this->maxAcquire, 0666, 1);
    }
    
    public function release()
    {
        if (!$this->sem) {
            mwarning("Releasing while not initialized!");
            
            return;
        }
        
        mdebug("Releasing semaphore for %x", $this->key);
        sem_release($this->sem);
        mdebug("Released");
    }
    
    public function remove()
    {
        if (!$this->sem) {
            $this->initialize();
        }
        
        mdebug("Removing semaphore for key: %x", $this->key);
        sem_remove($this->sem);
        $this->sem = null;
        $this->key = '';
    }
    
    /**
     * @return a|string
     */
    public function getId()
    {
        return $this->id;
    }
}
