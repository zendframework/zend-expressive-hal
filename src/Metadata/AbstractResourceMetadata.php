<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

abstract class AbstractResourceMetadata extends AbstractMetadata
{
    /**
     * Service name of an ExtractionInterface implementation to use when
     * extracting a resource of this type.
     * @var string
     */
    protected $extractor;

    public function getExtractor() : string
    {
        return $this->extractor;
    }
}
