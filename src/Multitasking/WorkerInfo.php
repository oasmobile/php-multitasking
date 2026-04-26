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
    private readonly string $id;
    private readonly \Closure $worker;
    private ?int $currentWorkerIndex = null;
    private ?int $totalWorkers = null;
    private ?int $numberOfConcurrentWorkers = null;
    private ?int $exitStatus = null;
    
    public function __construct(callable $worker)
    {
        $this->id     = \md5(\sprintf("%s,%s,%s", \spl_object_hash($this), \microtime(true), \getmypid()));
        $this->worker = \Closure::fromCallable($worker);
    }
    
    public function getCurrentWorkerIndex(): ?int
    {
        return $this->currentWorkerIndex;
    }
    
    public function setCurrentWorkerIndex(int $currentWorkerIndex): void
    {
        $this->currentWorkerIndex = $currentWorkerIndex;
    }
    
    public function getExitStatus(): ?int
    {
        return $this->exitStatus;
    }
    
    public function setExitStatus(int $exitStatus): void
    {
        $this->exitStatus = $exitStatus;
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getNumberOfConcurrentWorkers(): ?int
    {
        return $this->numberOfConcurrentWorkers;
    }
    
    public function setNumberOfConcurrentWorkers(int $numberOfConcurrentWorkers): void
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
    }
    
    public function getTotalWorkers(): ?int
    {
        return $this->totalWorkers;
    }
    
    public function setTotalWorkers(int $totalWorkers): void
    {
        $this->totalWorkers = $totalWorkers;
    }
    
    public function getWorker(): callable
    {
        return $this->worker;
    }
}
