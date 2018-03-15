<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Exception;

use InvalidArgumentException;
use Zend\Expressive\Hal\HalResource;

use function gettype;
use function sprintf;

class InvalidObjectException extends InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param mixed $value Non-object value.
     */
    public static function forNonObject($value) : self
    {
        return new self(sprintf(
            'Cannot generate %s for non-object value of type "%s"',
            HalResource::class,
            gettype($value)
        ));
    }

    public static function forUnknownType(string $class) : self
    {
        return new self(sprintf(
            'Cannot generate %s for object of type %s; not in metadata map',
            HalResource::class,
            $class
        ));
    }
}
