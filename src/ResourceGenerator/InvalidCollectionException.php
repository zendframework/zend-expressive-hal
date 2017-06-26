<?php

namespace Hal\ResourceGenerator;

use RuntimeException;

class InvalidCollectionException extends RuntimeException implements Exception
{
    /**
     * @param mixed $instance The invalid collection instance or value.
     */
    public static function fromInstance($instance, string $class) : self
    {
        return new self(sprintf(
            '%s is unable to create a resource for collection of type "%s"; not a Traversable',
            $class,
            is_object($instance) ? get_class($instance) : gettype($instance)
        ));
    }
}
