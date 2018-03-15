<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Exception;

use RuntimeException;
use Zend\Expressive\Hal\HalResource;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class InvalidResourceValueException extends RuntimeException implements ExceptionInterface
{
    public static function fromValue($value) : self
    {
        return new self(sprintf(
            'Encountered non-primitive type "%s" when serializing %s instance; unable to serialize',
            is_object($value) ? get_class($value) : gettype($value),
            HalResource::class
        ));
    }

    /**
     * @param object $object
     */
    public static function fromObject($object) : self
    {
        return new self(sprintf(
            'Encountered object of type "%s" when serializing %s instance; unable to serialize',
            get_class($object),
            HalResource::class
        ));
    }
}
