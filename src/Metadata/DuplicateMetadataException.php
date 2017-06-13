<?php

namespace Hal\Metadata;

use DomainException;

class DuplicateMetadataException extends DomainException implements Exception
{
    public static function create(string $class)
    {
        return new self(sprintf(
            'Attempted to add metadata for class "%s", which already has metadata in the map',
            $class
        ));
    }
}
