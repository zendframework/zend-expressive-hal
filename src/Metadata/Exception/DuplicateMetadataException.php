<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata\Exception;

use DomainException;

use function sprintf;

class DuplicateMetadataException extends DomainException implements ExceptionInterface
{
    public static function create(string $class)
    {
        return new self(sprintf(
            'Attempted to add metadata for class "%s", which already has metadata in the map',
            $class
        ));
    }
}
