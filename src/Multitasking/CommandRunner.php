<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-30
 * Time: 15:24
 */

namespace Oasis\Mlib\Multitasking;

class CommandRunner
{
    protected $parentProcessId = 0;
    /** @var  int */
    protected $numberOfConcurrentWorkers;
    /** @var callable[] */
    protected $workers            = [];
    protected $runningProcesses   = [];
    protected $startedWorkerCount = 0;
    protected $totalWorkerCount   = 0;
    
    public function __construct($numberOfConcurrentWorkers = 1)
    {
        $this->numberOfConcurrentWorkers = $numberOfConcurrentWorkers;
        
        $this->parentProcessId = getmypid();
    }
    
    public function addWorker(callable $worker, $count = 1)
    {
        for ($i = 0; $i < $count; ++$i) {
            $this->workers[] = $worker;
        }
    }
    
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
        
        $this->totalWorkerCount   = count($this->workers);
        $this->startedWorkerCount = 0;
        while ($this->hasMoreWork()) {
            $this->executeWorker();
        }
        
        return $this->startedWorkerCount;
    }
    
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
                
                unset($this->runningProcesses[$pid]);
                
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
    }
    
    public function hasMoreWork()
    {
        return count($this->runningProcesses) < $this->numberOfConcurrentWorkers
               && count($this->workers) > 0;
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
        
        $worker = array_shift($this->workers);
        
        $info                            = new WorkerInfo();
        $info->totalWorkers              = $this->totalWorkerCount;
        $info->currentWorkerIndex        = $this->startedWorkerCount;
        $info->numberOfConcurrentWorkers = $this->numberOfConcurrentWorkers;
        
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
            $this->runningProcesses[$pid] = true;
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
