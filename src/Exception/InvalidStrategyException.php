<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Exception;

use InvalidArgumentException;
use Zend\Expressive\Hal\ResourceGenerator\StrategyInterface;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class InvalidStrategyException extends InvalidArgumentException implements ExceptionInterface
{
    public static function forType(string $strategy) : self
    {
        return new self(sprintf(
            'Invalid strategy "%s"; does not exist, or does not implement %s',
            $strategy,
            StrategyInterface::class
        ));
    }

    /**
     * @param mixed $strategy
     */
    public static function forInstance($strategy) : self
    {
        return new self(sprintf(
            'Invalid strategy of type "%s"; does not implement %s',
            is_object($strategy) ? get_class($strategy) : gettype($strategy),
            StrategyInterface::class
        ));
    }
}
