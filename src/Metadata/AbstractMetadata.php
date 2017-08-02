<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

use Zend\Expressive\Hal\LinkCollection;

abstract class AbstractMetadata
{
    use LinkCollection;

    /**
     * @var string
     */
    protected $class;

    public function getClass() : string
    {
        return $this->class;
    }
}
