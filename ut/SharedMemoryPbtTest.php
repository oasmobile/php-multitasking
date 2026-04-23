<?php

use Eris\Generator;
use Eris\TestTrait;
use Oasis\Mlib\Multitasking\SharedMemory;

class SharedMemoryPbtTest extends \PHPUnit\Framework\TestCase
{
    use TestTrait;

    protected SharedMemory $memory;

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
        $this->memory = new SharedMemory(uniqid(__CLASS__ . '#', true));
        $this->memory->initialize();
    }

    protected function tearDown(): void
    {
        $this->memory->remove();
        parent::tearDown();
    }

    /**
     * Feature: release-2.0.0, Property 1: SharedMemory round-trip
     *
     * For any serializable value and any non-empty string key,
     * set(key, value) followed by get(key) returns the original value.
     */
    public function testRoundTrip(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\oneOf(
                Generator\int(),
                Generator\string(),
                Generator\bool(),
                Generator\float()
            )
        )->then(function (string $key, mixed $value): void {
            if ($key === '') {
                return;
            }
            $this->memory->set($key, $value);
            $result = $this->memory->get($key);
            $this->assertEquals($value, $result);
            $this->memory->delete($key);
        });
    }
}
