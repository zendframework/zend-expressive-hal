<?php

namespace Hal;

use InvalidArgumentException;

class InvalidStrategyException extends InvalidArgumentException implements Exception
{
    public static function forType(string $strategy) : self
    {
        return new self(sprintf(
            'Invalid strategy "%s"; does not exist, or does not implement %s',
            $strategy,
            ResourceGenerator\Strategy::class
        ));
    }

    /**
     * @param mixed $strategy
     */
    public static function forInstance($strategy) : self
    {
        return new self(sprintf(
            'Invalid strategy of type "%s"; does not implement %s',
            is_object($strategy) ? get_class($strategy) : gettype($strategy),
            ResourceGenerator\Strategy::class
        ));
    }
}
