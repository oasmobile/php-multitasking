<?php

use Eris\Generator;
use Eris\TestTrait;
use Oasis\Mlib\Multitasking\Semaphore;

class SemaphorePbtTest extends \PHPUnit\Framework\TestCase
{
    use TestTrait;

    protected Semaphore $sem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sem = new Semaphore(uniqid(__CLASS__ . '#', true));
        $this->sem->initialize();
    }

    protected function tearDown(): void
    {
        $this->sem->remove();
        parent::tearDown();
    }

    /**
     * Feature: release-2.0.0, Property 3: Semaphore idempotence
     *
     * For any positive integer n, performing n acquire/release cycles
     * leaves the semaphore in a consistent, reusable state.
     */
    public function testIdempotence(): void
    {
        $this->forAll(
            Generator\choose(1, 50)
        )->then(function (int $n): void {
            for ($i = 0; $i < $n; $i++) {
                $acquired = $this->sem->acquire();
                $this->assertTrue($acquired);
                $this->sem->release();
            }
            // Verify final state is consistent: can still acquire/release
            $finalAcquired = $this->sem->acquire();
            $this->assertTrue($finalAcquired);
            $this->sem->release();
        });
    }
}
