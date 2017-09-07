<?php
use Oasis\Mlib\Event\Event;
use Oasis\Mlib\Multitasking\BackgroundWorkerManager;
use Oasis\Mlib\Multitasking\WorkerInfo;
use Oasis\Mlib\Multitasking\WorkerManagerCompletedEvent;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-30
 * Time: 15:23
 */
class BackgroundWorkerManagerTest extends PHPUnit_Framework_TestCase
{
    public function testNormalRun()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'multitasking-ut-');
        mdebug("Temp file: %s", $tempFile);
        
        $runner  = new BackgroundWorkerManager();
        $started = $runner->run();
        self::assertEquals(0, $started);
        $runner->addWorker(
            function () use ($tempFile) {
                file_put_contents($tempFile, 'abcd');
            }
        );
        $started = $runner->run();
        self::assertEquals(1, $started);
        $runner->wait();
        
        $written = file_get_contents($tempFile);
        
        self::assertEquals('abcd', $written);
    }
    
    public function testRunWithMultipleWorkers()
    {
        $num  = 10;
        $size = 5;
        
        $files = [];
        for ($i = 0; $i < $num; ++$i) {
            $files[$i] = tempnam(sys_get_temp_dir(), 'multitasking-ut-');
        }
        
        $runner = new BackgroundWorkerManager();
        $runner->setNumberOfConcurrentWorkers($size);
        $runner->addWorker(
            function (WorkerInfo $info) use ($files) {
                mdebug("Writing to %s with %s", $files[$info->getCurrentWorkerIndex()], $info->getCurrentWorkerIndex());
                file_put_contents($files[$info->getCurrentWorkerIndex()], $info->getCurrentWorkerIndex());
            },
            $num
        );
        $started = $runner->run();
        self::assertEquals($size, $started);
        $runner->wait();
        
        for ($i = 0; $i < $num; ++$i) {
            mdebug("Checking file: %s", $files[$i]);
            self::assertEquals($i, file_get_contents($files[$i]));
        }
    }
    
    public function testEventDispatchedByFinishedWorker()
    {
        $runner = new BackgroundWorkerManager();
        $ids    = $runner->addWorker(
            function () {
                return true;
            },
            10
        );
        list($failedId) = $runner->addWorker(
            function () {
                die(111);
            }
        );
        $ids[] = $failedId;
        $runner->setNumberOfConcurrentWorkers(1);
        $runner->addEventListener(
            BackgroundWorkerManager::EVENT_WORKER_FINISHED,
            function (Event $event) use (&$ids) {
                $id = array_shift($ids);
                /** @var WorkerInfo $info */
                $info = $event->getContext();
                $this->assertEquals($id, $info->getId());
            }
        );
        $runner->addEventListener(
            BackgroundWorkerManager::EVENT_ALL_COMPLETED,
            function (WorkerManagerCompletedEvent $event) use ($failedId) {
                $this->assertFalse($event->isSuccessful());
                $this->assertEquals(10, count($event->getSuccessfulWorkers()));
                $this->assertEquals(1, count($event->getFailedWorkers()));
                $this->assertArrayHasKey($failedId, $event->getFailedWorkers());
            }
        );
        $runner->run();
        $runner->wait();
    }
}
