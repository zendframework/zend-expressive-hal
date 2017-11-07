<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\TestAsset;

use Zend\Expressive\Hal\Metadata\MetadataMapFactory;

class TestMetadataMapFactory extends MetadataMapFactory
{
    protected function createTestMetadata(array $metadata) : TestMetadata
    {
        return new TestMetadata();
    }
}
