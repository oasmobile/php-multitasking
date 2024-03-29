<?php
use Oasis\Mlib\Multitasking\BackgroundWorkerManager;
use Oasis\Mlib\Multitasking\Semaphore;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-31
 * Time: 20:27
 */
class SemaphoreTest extends PHPUnit_Framework_TestCase
{
    public function testNormalCase()
    {
        $sem = new Semaphore('');
        $sem->acquire();
        $sem->release();
        $sem->remove();
    }
    
    public function testMultipleProcess()
    {
        $sem      = new Semaphore('');
        $tempFile = tempnam(sys_get_temp_dir(), 'semaphore-ut-');
        
        $worker1 = function () use ($sem, $tempFile) {
            $sem->acquire();
            file_put_contents($tempFile, 'abc', FILE_APPEND);
            $sem->release();
        };
        $worker2 = function () use ($sem, $tempFile) {
            usleep(500000);
            $sem->acquire();
            file_put_contents($tempFile, 'def', FILE_APPEND);
            $sem->release();
        };
        
        $man = new BackgroundWorkerManager(2);
        $man->addWorker($worker1);
        $man->addWorker($worker2);
        $man->run();
        $man->wait();
        
        $sem->remove();
        
        self::assertEquals('abcdef', file_get_contents($tempFile));
    }
    
    public function testAutoRelease()
    {
        $sem      = new Semaphore('');
        $tempFile = tempnam(sys_get_temp_dir(), 'semaphore-ut-');
        
        $worker1 = function () use ($sem, $tempFile) {
            $sem->acquire();
            file_put_contents($tempFile, 'abc', FILE_APPEND);
        };
        $worker2 = function () use ($sem, $tempFile) {
            usleep(500000);
            $sem->acquire();
            file_put_contents($tempFile, 'def', FILE_APPEND);
            $sem->release();
        };
        
        $man = new BackgroundWorkerManager(2);
        $man->addWorker($worker1);
        $man->addWorker($worker2);
        $man->run();
        $man->wait();
        
        $sem->remove();
        
        self::assertEquals('abcdef', file_get_contents($tempFile));
    }
    
    public function testNonBlockingMode()
    {
        $sem      = new Semaphore('');
        $tempFile = tempnam(sys_get_temp_dir(), 'semaphore-ut-');
        
        $worker1 = function () use ($sem, $tempFile) {
            $sem->acquire();
            file_put_contents($tempFile, 'abc', FILE_APPEND);
            usleep(1000000);
            file_put_contents($tempFile, 'ghi', FILE_APPEND);
            $sem->release();
        };
        $worker2 = function () use ($sem, $tempFile) {
            usleep(500000);
            $ret = $sem->acquire(true);
            file_put_contents($tempFile, $ret ? 'def' : 'fed', FILE_APPEND);
            //$sem->release();
        };
        
        $man = new BackgroundWorkerManager(2);
        $man->addWorker($worker1);
        $man->addWorker($worker2);
        $man->run();
        $man->wait();
        
        $sem->remove();
        
        self::assertEquals('abcfedghi', file_get_contents($tempFile));
    }

    public function testWithLock()
    {
        $sem      = new Semaphore('');
        $tempFile = tempnam(sys_get_temp_dir(), 'semaphore-ut-');

        $worker1 = function () use ($sem, $tempFile) {
            $sem->withLock(function () use ($tempFile) {
                file_put_contents($tempFile, 'abc', FILE_APPEND);
            });
        };
        $worker2 = function () use ($sem, $tempFile) {
            usleep(500000);
            $sem->withLock(function () use ($tempFile) {
                file_put_contents($tempFile, 'def', FILE_APPEND);
            });
        };

        $man = new BackgroundWorkerManager(2);
        $man->addWorker($worker1);
        $man->addWorker($worker2);
        $man->run();
        $man->wait();

        $sem->remove();

        self::assertEquals('abcdef', file_get_contents($tempFile));
    }

    public function testWithLockReturnValue()
    {
        $sem = new Semaphore('');

        $result = $sem->withLock(function () {
            return "Hello, World!";
        });

        self::assertEquals("Hello, World!", $result);
    }

}
