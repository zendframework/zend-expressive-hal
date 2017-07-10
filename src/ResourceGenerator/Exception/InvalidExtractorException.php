<?php

namespace Hal\ResourceGenerator\Exception;

use RuntimeException;
use Zend\Hydrator\ExtractionInterface;

class InvalidExtractorException extends RuntimeException implements Exception
{
    /**
     * @param mixed $extractor
     */
    public static function fromInstance($extractor) : self
    {
        return new self(sprintf(
            'Invalid extractor "%s" provided in metadata; does not implement %s',
            is_object($extractor) ? get_class($extractor) : gettype($extractor),
            ExtractionInterface::class
        ));
    }
}
