<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\TestAsset;

class Uri
{
    private $uri;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    public function __toString() : string
    {
        return $this->uri;
    }
}
