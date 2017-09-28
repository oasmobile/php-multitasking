<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-31
 * Time: 11:52
 */

namespace Oasis\Mlib\Multitasking;

class WorkerInfo
{
    /** @var string */
    private $id;
    /** @var callable */
    private $worker;
    /** @var int */
    private $currentWorkerIndex = null;
    /** @var int */
    private $totalWorkers = null;
    /** @var int */
    private $numberOfConcurrentWorkers = null;
    /** @var int */
    private $exitStatus = null;
    
    public function __construct(callable $worker)
    {
        $this->id     = \md5(\sprintf("%s,%s,%s", \spl_object_hash($this), \microtime(true), \getmypid()));
        $this->worker = $worker;
    }
    
    /**
     * @return int
     */
    public function getCurrentWorkerIndex()
    {
        return $this->currentWorkerIndex;
    }
    
    /**
     * @param int $currentWorkerIndex
     */
    public function setCurrentWorkerIndex($currentWorkerIndex)
    {
        $this->currentWorkerIndex = $currentWorkerIndex;
    }
    
    /**
     * @return int
     */
    public function getExitStatus()
    {
        return $this->exitStatus;
    }
    
    /**
     * @param int $exitStatus
     */
    public function setExitStatus( $exitStatus)
    {
        $this->exitStatus = $exitStatus;
    }
    
    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @return int
     */
    public function getNumberOfConcurrentWorkers()
    {
        return $this->numberOfConcurrentWorkers;
    }
    
    /**
     * @param int $numberOfConcurrentWorkers
     */
    public function setNumberOfConcurrentWorkers( $numberOfConcurrentWorkers)
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
    }
    
    /**
     * @return int
     */
    public function getTotalWorkers()
    {
        return $this->totalWorkers;
    }
    
    /**
     * @param int $totalWorkers
     */
    public function setTotalWorkers( $totalWorkers)
    {
        $this->totalWorkers = $totalWorkers;
    }
    
    /**
     * @return callable
     */
    public function getWorker()
    {
        return $this->worker;
    }
}
