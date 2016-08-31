<?php
use Oasis\Mlib\Multitasking\BackgroundWorkerManager;
use Oasis\Mlib\Multitasking\WorkerInfo;

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
                mdebug("Writing to %s with %s", $files[$info->currentWorkerIndex], $info->currentWorkerIndex);
                file_put_contents($files[$info->currentWorkerIndex], $info->currentWorkerIndex);
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
}
