<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator\Exception;

use RuntimeException;
use Zend\Expressive\Hal\ResourceGenerator;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class InvalidConfigException extends RuntimeException implements ExceptionInterface
{
    /**
     * @param mixed $config
     */
    public static function dueToNonArray($config) : self
    {
        return new self(sprintf(
            'Invalid %s configuration; expected an array or ArrayAccess instance, but received %s',
            ResourceGenerator::class,
            is_object($config) ? get_class($config) : gettype($config)
        ));
    }

    /**
     * @param mixed $strategies
     */
    public static function dueToInvalidStrategies($strategies) : self
    {
        return new self(sprintf(
            'Invalid zend-expressive-hal.resource-generator.strategies configuration; '
            . 'expected an array or Traversable instance, but received %s',
            is_object($strategies) ? get_class($strategies) : gettype($strategies)
        ));
    }
}
