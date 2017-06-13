<?php

namespace Hal\Metadata;

use UnexpectedValueException;

class UndefinedClassException extends UnexpectedValueException implements Exception
{
    public static function create($class)
    {
        return new self(sprintf(
            'Cannot map metadata for class "%s"; class does not exist',
            $class
        ));
    }
}
