<?php

declare(strict_types=1);

namespace Zenigata\Helpers\Test\Unit;

use ArrayAccess;
use ArrayObject;
use DateTimeInterface;
use InvalidArgumentException;
use Iterator;
use stdClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zenigata\Helpers\ContainerResolver;
use Zenigata\Testing\Infrastructure\FakeContainer;

/**
 * Unit test for {@see ContainerResolver} utility.
 *
 * This test suite verifies the behavior of the {@see ContainerResolver::resolve()} 
 * method, which is responsible for retrieving entries from a PSR-11 container 
 * and optionally validating them against one or more expected types.
 *
 * Covered cases:
 * 
 * - Resolves a service without any type validation.
 * - Resolves a service that matches the expected type.
 * - Throws an exception if the requested identifier does not exist in the container.
 * - Throws an exception if the resolved service does not match the expected type(s).
 * - Accepts and validates against multiple expected types, succeeding if at least one matches.
 */
#[CoversClass(ContainerResolver::class)]
final class ContainerResolverTest extends TestCase
{
    public function testResolvesServiceWithoutTypeCheck(): void
    {
        $container = new FakeContainer([
            'foo' => new stdClass(),
        ]);

        $service = ContainerResolver::resolve($container, 'foo');

        $this->assertInstanceOf(stdClass::class, $service);
    }

    public function testResolvesServiceWithCorrectType(): void
    {
        $expected = new stdClass();
        $container = new FakeContainer([
            'foo' => $expected,
        ]);

        $service = ContainerResolver::resolve($container, 'foo', stdClass::class);

        $this->assertSame($expected, $service);
    }

    public function testThrowsIfIdentifierNotFound(): void
    {
        $container = new FakeContainer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Identifier 'missing' not found in container.");

        ContainerResolver::resolve($container, 'missing');
    }

    public function testThrowsIfServiceDoesNotMatchExpectedType(): void
    {
        $container = new FakeContainer([
            'foo' => new stdClass(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid type for identifier 'foo'. Expected one of");

        ContainerResolver::resolve($container, 'foo', DateTimeInterface::class);
    }

    public function testAcceptsMultipleExpectedTypes(): void
    {
        $expected = new ArrayObject();
        $container = new FakeContainer([
            'foo' => $expected,
        ]);

        $service = ContainerResolver::resolve($container, 'foo', [Iterator::class, ArrayAccess::class]);

        $this->assertSame($expected, $service);
    }
}