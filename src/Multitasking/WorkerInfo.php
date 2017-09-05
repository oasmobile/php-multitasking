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
    public function getCurrentWorkerIndex(): int
    {
        return $this->currentWorkerIndex;
    }
    
    /**
     * @param int $currentWorkerIndex
     */
    public function setCurrentWorkerIndex(int $currentWorkerIndex)
    {
        $this->currentWorkerIndex = $currentWorkerIndex;
    }
    
    /**
     * @return int
     */
    public function getExitStatus(): int
    {
        return $this->exitStatus;
    }
    
    /**
     * @param int $exitStatus
     */
    public function setExitStatus(int $exitStatus)
    {
        $this->exitStatus = $exitStatus;
    }
    
    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * @return int
     */
    public function getNumberOfConcurrentWorkers(): int
    {
        return $this->numberOfConcurrentWorkers;
    }
    
    /**
     * @param int $numberOfConcurrentWorkers
     */
    public function setNumberOfConcurrentWorkers(int $numberOfConcurrentWorkers)
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
    }
    
    /**
     * @return int
     */
    public function getTotalWorkers(): int
    {
        return $this->totalWorkers;
    }
    
    /**
     * @param int $totalWorkers
     */
    public function setTotalWorkers(int $totalWorkers)
    {
        $this->totalWorkers = $totalWorkers;
    }
    
    /**
     * @return callable
     */
    public function getWorker(): callable
    {
        return $this->worker;
    }
}
