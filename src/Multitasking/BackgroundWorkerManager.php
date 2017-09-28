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
    
    protected $parentProcessId = 0;
    /** @var  int */
    protected $numberOfConcurrentWorkers;
    /** @var WorkerInfo[] */
    protected $pendingWorkers = [];
    /** @var WorkerInfo[] */
    protected $runningProcesses = [];
    /** @var WorkerInfo[] */
    protected $successfulProcesses = [];
    /** @var WorkerInfo[] */
    protected $failedProcesses    = [];
    protected $startedWorkerCount = 0;
    protected $totalWorkerCount   = 0;
    
    public function __construct($numberOfConcurrentWorkers = 1)
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
        
        $this->parentProcessId = getmypid();
    }
    
    /**
     * @param callable $worker
     * @param int      $count
     *
     * @return array ID:s of added worker. Those ID:s can be used to match against worker finished event dispatched by
     *               the manager.
     */
    public function addWorker(callable $worker, $count = 1)
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
    public function run()
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
    public function wait()
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
    
    public function hasMoreWork()
    {
        return count($this->runningProcesses) < $this->numberOfConcurrentWorkers
               && count($this->pendingWorkers) > 0;
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
    public function setNumberOfConcurrentWorkers($numberOfConcurrentWorkers)
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
    }
    
    protected function executeWorker()
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
            $ret = call_user_func($worker, $info);
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
    
    protected function assertInParentProcess()
    {
        if (getmypid() != $this->parentProcessId) {
            throw new \RuntimeException("Cannot run a command runner in children processes");
        }
    }
}
