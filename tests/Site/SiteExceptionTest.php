<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SiteException::class)]
class SiteExceptionTest extends TestCase
{
    private SiteException $exception;

    protected function setUp(): void
    {
        $this->exception = new SiteException('Something went wrong');
    }

    #[Test]
    public function testContextIsEmptyByDefault(): void
    {
        $this->assertSame([], $this->exception->getContext());
    }

    #[Test]
    public function testWithContextReturnsSameInstance(): void
    {
        $result = $this->exception->withContext('order', ['id' => 1]);

        $this->assertSame($this->exception, $result);
    }

    #[Test]
    public function testWithContextStoresNamedContext(): void
    {
        $this->exception->withContext('order', ['id' => 123, 'total' => 99.99]);

        $this->assertSame(
            ['order' => ['id' => 123, 'total' => 99.99]],
            $this->exception->getContext(),
        );
    }

    #[Test]
    public function testMultipleContextsAccumulate(): void
    {
        $this->exception
            ->withContext('order', ['id' => 123])
            ->withContext('customer', ['id' => 456])
            ->withContext('status', ['paid' => true]);

        $this->assertSame(
            [
                'order'    => ['id' => 123],
                'customer' => ['id' => 456],
                'status'   => ['paid' => true],
            ],
            $this->exception->getContext(),
        );
    }

    #[Test]
    public function testSameKeyOverwritesPreviousContext(): void
    {
        $this->exception
            ->withContext('order', ['id' => 1])
            ->withContext('order', ['id' => 2]);

        $this->assertSame(
            ['order' => ['id' => 2]],
            $this->exception->getContext(),
        );
    }

    #[Test]
    public function testWithContextPreservesSubclassType(): void
    {
        $sub_class = new class('test') extends SiteException {};

        $result = $sub_class->withContext('key', ['value' => 'data']);

        $this->assertInstanceOf($sub_class::class, $result);
    }

    #[Test]
    public function testTagsAreEmptyByDefault(): void
    {
        $this->assertSame([], $this->exception->getTags());
    }

    #[Test]
    public function testWithTagReturnsSameInstance(): void
    {
        $result = $this->exception->withTag('size', 'large');

        $this->assertSame($this->exception, $result);
    }

    #[Test]
    public function testWithTagStoresTag(): void
    {
        $this->exception->withTag('size', 'large');

        $this->assertSame(
            ['size' => 'large'],
            $this->exception->getTags()
        );
    }

    #[Test]
    public function testSameTagKeyOverwritesPrevious(): void
    {
        $this->exception->withTag('size', 'large')
            ->withTag('size', 'small');

        $this->assertSame(
            ['size' => 'small'],
            $this->exception->getTags()
        );
    }
}
