<?php

namespace Hal\ResourceGenerator;

use Hal\Metadata\AbstractMetadata;
use RuntimeException;

class UnexpectedMetadataTypeException extends RuntimeException implements Exception
{
    public static function forMetadata(AbstractMetadata $metadata, string $strategy, string $expected) : self
    {
        return new self(sprintf(
            'Unexpected metadata of type %s was mapped to %s (expects %s)',
            get_class($metadata),
            $strategy,
            $expected
        ));
    }
}
