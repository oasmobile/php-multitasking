<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-30
 * Time: 15:24
 */

namespace Oasis\Mlib\Multitasking;

use Oasis\Mlib\Event\EventDispatcherInterface;
use Oasis\Mlib\Event\EventDispatcherTrait;

class BackgroundWorkerManager implements EventDispatcherInterface
{
    use EventDispatcherTrait;
    
    const EVENT_WORKER_FINISHED = 'worker_finished';
    const EVENT_ALL_COMPLETED   = 'all_completed';
    
    protected readonly int $parentProcessId;
    protected int $numberOfConcurrentWorkers;
    /** @var WorkerInfo[] */
    protected array $pendingWorkers = [];
    /** @var WorkerInfo[] */
    protected array $runningProcesses = [];
    /** @var WorkerInfo[] */
    protected array $successfulProcesses = [];
    /** @var WorkerInfo[] */
    protected array $failedProcesses    = [];
    protected int $startedWorkerCount = 0;
    protected int $totalWorkerCount   = 0;
    
    public function __construct(int $numberOfConcurrentWorkers = 1)
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
        
        $this->parentProcessId = getmypid();
    }
    
    /**
     * @return array ID:s of added worker. Those ID:s can be used to match against worker finished event dispatched by
     *               the manager.
     */
    public function addWorker(callable $worker, int $count = 1): array
    {
        $ret = [];
        for ($i = 0; $i < $count; ++$i) {
            $info                   = new WorkerInfo($worker);
            $this->pendingWorkers[] = $info;
            $ret[]                  = $info->getId();
        }
        
        return $ret;
    }
    
    /**
     * @return int num of started workers (always <= num-of-concurrent-workers)
     */
    public function run(): int
    {
        $this->assertInParentProcess();
        
        if ($this->runningProcesses) {
            throw new \RuntimeException("Command runner is already running!");
        }
        
        if (!$this->hasMoreWork()) {
            minfo("Nothing to run, did you forget to add some workers?");
            
            return 0;
        }
        
        $this->successfulProcesses = [];
        $this->failedProcesses     = [];
        $this->totalWorkerCount    = count($this->pendingWorkers);
        $this->startedWorkerCount  = 0;
        while ($this->hasMoreWork()) {
            $this->executeWorker();
        }
        
        return $this->startedWorkerCount;
    }
    
    /**
     * Wait for all workers to finish
     *
     * @return int num of failed workers
     */
    public function wait(): int
    {
        $this->assertInParentProcess();
        
        while (true) {
            
            $status = 0;
            $pid    = pcntl_waitpid(-1, $status, WNOHANG);
            
            if ($pid == 0) {
                // no child process has quit
                usleep(200 * 1000);
            }
            else if ($pid > 0) {
                // child process with pid = $pid exits
                $exitStatus = pcntl_wexitstatus($status);
                mnotice("Process #%d has quit with code %d", $pid, $exitStatus);
                $info = isset($this->runningProcesses[$pid]) ? $this->runningProcesses[$pid] : null;
                
                if (!$info) {
                    \merror("A pid not managed is encountered! pid = %s", $pid);
                }
                else {
                    unset($this->runningProcesses[$pid]);
                    $info->setExitStatus($exitStatus);
                    $this->dispatch(self::EVENT_WORKER_FINISHED, $info);
                    
                    if ($exitStatus == 0) {
                        $this->successfulProcesses[$info->getId()] = $info;
                    }
                    else {
                        $this->failedProcesses[$info->getId()] = $info;
                    }
                }
                
                if ($this->hasMoreWork()) {
                    $this->executeWorker();
                }
            }
            else {
                // error
                $errno = pcntl_get_last_error();
                if ($errno == PCNTL_ECHILD) {
                    // all children finished
                    mdebug("No more BackgroundProcessRunner children, finish waiting...");
                    break;
                }
                else {
                    // some other error
                    throw new \RuntimeException("Error waiting for process, error = " . pcntl_strerror($errno));
                }
            }
        }
        
        $this->dispatch(new WorkerManagerCompletedEvent($this->successfulProcesses, $this->failedProcesses));
        
        return count($this->failedProcesses);
    }
    
    public function hasMoreWork(): bool
    {
        return count($this->runningProcesses) < $this->numberOfConcurrentWorkers
               && count($this->pendingWorkers) > 0;
    }
    
    public function getNumberOfConcurrentWorkers(): int
    {
        return $this->numberOfConcurrentWorkers;
    }
    
    public function setNumberOfConcurrentWorkers(int $numberOfConcurrentWorkers): void
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
    }
    
    protected function executeWorker(): void
    {
        $this->assertInParentProcess();
        
        /** @var WorkerInfo $info */
        $info = array_shift($this->pendingWorkers);
        $info->setTotalWorkers($this->totalWorkerCount);
        $info->setCurrentWorkerIndex($this->startedWorkerCount);
        $info->setNumberOfConcurrentWorkers($this->numberOfConcurrentWorkers);
        $worker = $info->getWorker();
        
        $pid = pcntl_fork();
        if ($pid < 0) {
            throw new \RuntimeException("Cannot fork process: " . pcntl_strerror(pcntl_get_last_error()));
        }
        elseif ($pid == 0) {
            // in child process
            $ret = $worker($info);
            if (!is_int($ret)) {
                $ret = 0;
            }
            exit($ret);
        }
        else {
            // in parent
            $this->runningProcesses[$pid] = $info;
            $this->startedWorkerCount++;
        }
    }
    
    protected function assertInParentProcess(): void
    {
        if (getmypid() != $this->parentProcessId) {
            throw new \RuntimeException("Cannot run a command runner in children processes");
        }
    }
}
