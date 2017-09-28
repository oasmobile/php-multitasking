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
    private $successfulWorkers;
    /**
     * @var WorkerInfo[]
     */
    private $failedWorkers;
    
    public function __construct($successfulWorkers, $failedWorkers)
    {
        parent::__construct(BackgroundWorkerManager::EVENT_ALL_COMPLETED);
        
        $this->successfulWorkers = $successfulWorkers;
        $this->failedWorkers     = $failedWorkers;
    }
    
    public function isSuccessful()
    {
        return count($this->failedWorkers) == 0;
    }
    
    /**
     * @return WorkerInfo[]
     */
    public function getFailedWorkers()
    {
        return $this->failedWorkers;
    }
    
    /**
     * @return WorkerInfo[]
     */
    public function getSuccessfulWorkers()
    {
        return $this->successfulWorkers;
    }
    
}
