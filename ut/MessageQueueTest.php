<?php
use Oasis\Mlib\Multitasking\BackgroundWorkerManager;
use Oasis\Mlib\Multitasking\MessageQueue;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-01
 * Time: 18:18
 */
class MessageQueueTest extends PHPUnit_Framework_TestCase
{
    /** @var  MessageQueue */
    protected $queue;
    
    protected function setUp()
    {
        parent::setUp();
        $this->queue = new MessageQueue(__FILE__);
    }
    
    protected function tearDown()
    {
        parent::tearDown();
        $this->queue->remove();
    }
    
    public function testNormalSendAndReceive()
    {
        $this->queue->send('abc');
        
        $this->queue->receive($msg, $type);
        
        self::assertEquals('abc', $msg);
    }
    
    public function testSendAndReceiveInDifferentProcess()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'message-queue-ut-');
        
        $worker1 = function () {
            sleep(1);
            $this->queue->send('abcdefg');
        };
        $worker2 = function () use ($tempFile) {
            $this->queue->receive($msg, $type);
            file_put_contents($tempFile, $msg);
        };
        
        $man = new BackgroundWorkerManager(2);
        $man->addWorker($worker1);
        $man->addWorker($worker2);
        $man->run();
        $man->wait();
        
        self::assertEquals('abcdefg', file_get_contents($tempFile));
    }


    /**
     * @small
     */
    public function testNonBlockingReceive()
    {
        $this->queue->receive($msg, $type, 0, false);
    }
    
    public function testSerialization()
    {
        $this->queue->send(new Memcached());
        $this->queue->receive($msg, $type);
        self::assertInstanceOf(Memcached::class, $msg);
    }
    
    public function testMessageType()
    {
        $this->queue->send('abc', 2);
        $this->queue->send('efg', 3);
        
        $ret = $this->queue->receive($msg, $type, 1, false);
        self::assertFalse($ret);
        $ret = $this->queue->receive($msg, $type, 2, false);
        self::assertTrue($ret);
        self::assertEquals('abc', $msg);
        self::assertEquals(2, $type);
        $ret = $this->queue->receive($msg, $type, -3, false);
        self::assertTrue($ret);
        self::assertEquals('efg', $msg);
    }
}
