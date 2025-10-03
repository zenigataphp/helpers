<?php

declare(strict_types=1);

namespace Zenigata\Helpers;

use function implode;
use function is_object;
use function sprintf;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Utility class for resolving container services with optional type validation.
 * 
 * Retrieves a service by ID and optionally ensures it matches one or more expected types.
 * This class is stateless and only operates on the cache instance passed to each method.
 */
class ContainerResolver
{
    /**
     * Prevent instantiation.
     */
    private function __construct() {}

    /**
     * Resolves an entry from the container by its identifier.
     *
     * If one or more $expectedTypes are provided, the resolved service will be
     * validated against them, throwing an exception if it doesn't match any.
     *
     * @param ContainerInterface $container  The container instance.
     * @param string             $id         Identifier of the entry to resolve.
     * @param string|string[]    $instanceOf Classes or interfaces to check; empty disables type validation.
     *
     * @return object The resolved service instance.
     * @throws InvalidArgumentException If the service is missing or does not match the expected type(s).
     */
    public static function resolve(
        ContainerInterface $container,
        string $id,
        string|array $instanceOf = ''
    ): object
    {
        if (!$container->has($id)) {
            throw new InvalidArgumentException(sprintf(
                "Identifier '%s' not found in container.",
                $id
            ));
        }

        $service = $container->get($id);

        if ($instanceOf === '' || $instanceOf === []) {
            return $service;
        }

        foreach ((array) $instanceOf as $expected) {
            if (is_object($service) && $service instanceof $expected) {
                return $service;
            }
        }

        throw new InvalidArgumentException(sprintf(
            "Invalid type for identifier '%s'. Expected one of [%s], got '%s'.",
            $id,
            implode(', ', (array) $instanceOf),
            $service::class
        ));
    }
}