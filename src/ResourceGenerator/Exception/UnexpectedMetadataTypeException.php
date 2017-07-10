<?php

namespace Hal\ResourceGenerator\Exception;

use Hal\Metadata\AbstractCollectionMetadata;
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

    public static function forCollection(AbstractMetadata $metadata, string $strategyClass) : self
    {
        return new self(sprintf(
            'Error extracting collection via strategy %s; expected %s instance, but received %s',
            $strategyClass,
            AbstractCollectionMetadata::class,
            get_class($metadata)
        ));
    }
}
