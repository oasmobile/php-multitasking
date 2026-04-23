<?php

use Eris\Generator;
use Eris\TestTrait;
use Oasis\Mlib\Multitasking\MessageQueue;

class MessageQueuePbtTest extends \PHPUnit\Framework\TestCase
{
    use TestTrait;

    protected MessageQueue $queue;

    /**
     * Override eris TestTrait method — PHPUnit 11 removed getAnnotations()
     * and PHPUnit\Util\Test::parseTestMethodAnnotations(). We don't use
     * eris-specific annotations, so returning an empty array is safe.
     */
    public function getTestCaseAnnotations(): array
    {
        return [];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = new MessageQueue(uniqid(__CLASS__ . '#', true));
        $this->queue->initialize();
    }

    protected function tearDown(): void
    {
        $this->queue->remove();
        parent::tearDown();
    }

    /**
     * Feature: release-2.0.0, Property 2: MessageQueue round-trip
     *
     * For any serializable message, send(msg) followed by receive()
     * returns the original message.
     */
    public function testRoundTrip(): void
    {
        $this->forAll(
            Generator\oneOf(
                Generator\int(),
                Generator\string(),
                Generator\bool()
            )
        )->then(function (mixed $msg): void {
            $this->queue->send($msg);
            $this->queue->receive($received, $type, expectedType: 0, blocking: false);
            $this->assertEquals($msg, $received);
        });
    }
}
