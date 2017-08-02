<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Exception;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class InvalidResponseBodyException extends RuntimeException implements Exception
{
    public static function forIncorrectStreamType() : self
    {
        return new self(sprintf(
            'The factory for generating a HAL response body stream did not return a %s instance',
            StreamInterface::class
        ));
    }

    public static function forNonWritableStream() : self
    {
        return new self(
            'The factory for generating a HAL response body stream returned a non-writable stream'
        );
    }
}
