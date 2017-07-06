<?php

namespace Hal;

use InvalidArgumentException;

class InvalidObjectException extends InvalidArgumentException implements Exception
{
    /**
     * @param mixed $value Non-object value.
     */
    public static function forNonObject($value) : self
    {
        return new self(sprintf(
            'Cannot generate %s for non-object value of type "%s"',
            HalResource::class,
            gettype($value)
        ));
    }

    public static function forUnknownType(string $class) : self
    {
        return new self(sprintf(
            'Cannot generate %s for object of type %s; not in metadata map',
            HalResource::class,
            $class
        ));
    }
}
