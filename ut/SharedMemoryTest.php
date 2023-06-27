<?php
use Oasis\Mlib\Multitasking\BackgroundWorkerManager;
use Oasis\Mlib\Multitasking\SharedMemory;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-09-01
 * Time: 21:07
 */
class SharedMemoryTest extends PHPUnit_Framework_TestCase
{
    /** @var  SharedMemory */
    protected $memory;
    
    protected function setUp()
    {
        parent::setUp();
        $this->memory = new SharedMemory(__FILE__);
    }
    
    protected function tearDown()
    {
        parent::tearDown();
        $this->memory->remove();
    }
    
    public function testSimpleSetGet()
    {
        $this->memory->set('abc', 1234);
        $val = $this->memory->get('abc');
        self::assertEquals(1234, $val);
    }
    
    public function testSerialization()
    {
        $this->memory->set('abc', new Memcached());
        $val = $this->memory->get('abc');
        self::assertInstanceOf(Memcached::class, $val);
    }
    
    public function testExistenceCheckAndDelete()
    {
        $has = $this->memory->has('abc');
        self::assertFalse($has);
        $this->memory->set('abc', 1234);
        $has = $this->memory->has('abc');
        self::assertTrue($has);
        $this->memory->delete('abc');
        $has = $this->memory->has('abc');
        self::assertFalse($has);
    }
    
    public function testSharedAcrossProcesses()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'shared-memory-ut-');
        
        $worker1 = function () {
            usleep(500000);
            $this->memory->set('abc', 5678);
        };
        $worker2 = function () use ($tempFile) {
            $has = $this->memory->has('abc');
            self::assertFalse($has);
            usleep(1000000);
            $has = $this->memory->has('abc');
            self::assertTrue($has);
            $val = $this->memory->get('abc');
            
            file_put_contents($tempFile, $val);
        };
        
        $man = new BackgroundWorkerManager(2);
        $man->addWorker($worker1);
        $man->addWorker($worker2);
        $man->run();
        $man->wait();
        
        self::assertEquals(5678, file_get_contents($tempFile));
    }

    public function testAccessingNonExistingKey()
    {
        $ret = $this->memory->get('non-existing-value');
        $this->assertNull($ret, "Non-existing value should return null");
    }

    public function testActOnKeyWithExistingValue()
    {
        $this->memory->set('counter', 5);

        $result = $this->memory->actOnKey('counter', function ($value) {
            return $value * 2;
        });

        self::assertEquals(10, $result);
        self::assertEquals(10, $this->memory->get('counter'));
    }

    public function testActOnKeyWithNonExistingValue()
    {
        $result = $this->memory->actOnKey('non_existing_key', function ($value) {
            return $value === null ? 'new_value' : $value;
        });

        self::assertEquals('new_value', $result);
        self::assertEquals('new_value', $this->memory->get('non_existing_key'));
    }

    public function testActOnKeyWithMultipleProcesses()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'shared-memory-ut-');
        $this->memory->set('counter', 0);

        $worker1 = function () {
            for ($i = 0; $i < 10; ++$i) {
                $this->memory->actOnKey('counter', function ($value) {
                    return $value + 1;
                });
                usleep(100000);
            }
        };

        $worker2 = function () use ($tempFile) {
            for ($i = 0; $i < 10; ++$i) {
                $this->memory->actOnKey('counter', function ($value) {
                    return $value + 1;
                });
                usleep(100000);
            }
            file_put_contents($tempFile, $this->memory->get('counter'));
        };

        $man = new BackgroundWorkerManager(2);
        $man->addWorker($worker1);
        $man->addWorker($worker2);
        $man->run();
        $man->wait();

        self::assertEquals(20, file_get_contents($tempFile));
    }
}
