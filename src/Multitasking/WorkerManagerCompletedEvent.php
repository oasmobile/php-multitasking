<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 07/09/2017
 * Time: 1:55 PM
 */

namespace Oasis\Mlib\Multitasking;

use Oasis\Mlib\Event\Event;

class WorkerManagerCompletedEvent extends Event
{
    /**
     * @var WorkerInfo[]
     */
    private readonly array $successfulWorkers;
    /**
     * @var WorkerInfo[]
     */
    private readonly array $failedWorkers;
    
    public function __construct(array $successfulWorkers, array $failedWorkers)
    {
        parent::__construct(BackgroundWorkerManager::EVENT_ALL_COMPLETED);
        
        $this->successfulWorkers = $successfulWorkers;
        $this->failedWorkers     = $failedWorkers;
    }
    
    public function isSuccessful(): bool
    {
        return count($this->failedWorkers) == 0;
    }
    
    /**
     * @return WorkerInfo[]
     */
    public function getFailedWorkers(): array
    {
        return $this->failedWorkers;
    }
    
    /**
     * @return WorkerInfo[]
     */
    public function getSuccessfulWorkers(): array
    {
        return $this->successfulWorkers;
    }
    
}
