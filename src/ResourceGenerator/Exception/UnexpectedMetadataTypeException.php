<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator\Exception;

use RuntimeException;
use Zend\Expressive\Hal\Metadata\AbstractCollectionMetadata;
use Zend\Expressive\Hal\Metadata\AbstractMetadata;

use function get_class;
use function sprintf;

class UnexpectedMetadataTypeException extends RuntimeException implements ExceptionInterface
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
