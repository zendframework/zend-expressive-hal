<?php

namespace Hal\Metadata\Exception;

use RuntimeException;

class UndefinedMetadataException extends RuntimeException implements Exception
{
    public static function create($class)
    {
        return new self(sprintf(
            'Unable to retrieve metadata for "%s"; no matching metadata found',
            $class
        ));
    }
}
