<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

class UrlBasedResourceMetadata extends AbstractResourceMetadata
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $class, string $url, string $extractor)
    {
        $this->class = $class;
        $this->url = $url;
        $this->extractor = $extractor;
    }

    public function getUrl() : string
    {
        return $this->url;
    }
}
