<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator\Exception;

use RuntimeException;
use Zend\Hydrator\ExtractionInterface;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class InvalidExtractorException extends RuntimeException implements ExceptionInterface
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
