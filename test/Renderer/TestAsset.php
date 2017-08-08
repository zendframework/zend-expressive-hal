<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal\Renderer;

use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;

trait TestAsset
{
    public function createExampleResource() : HalResource
    {
        $resource = new HalResource([
            'id'      => 'XXXX-YYYY-ZZZZ-ABAB',
            'example' => true,
            'foo'     => 'bar',
        ]);
        $resource = $resource->withLink(new Link('self', '/example/XXXX-YYYY-ZZZZ-ABAB'));
        $resource = $resource->withLink(new Link('shift', '/example/XXXX-YYYY-ZZZZ-ABAB/shift'));

        $bar = new HalResource([
            'id'   => 'BABA-ZZZZ-YYYY-XXXX',
            'bar'  => true,
            'some' => 'data',
        ]);
        $bar = $bar->withLink(new Link('self', '/bar/BABA-ZZZZ-YYYY-XXXX'));
        $bar = $bar->withLink(new Link('doc', '/doc/bar'));

        $baz = [];
        for ($i = 0; $i < 3; $i += 1) {
            $temp = new HalResource([
                'id' => 'XXXX-' . $i,
                'baz' => true,
            ]);
            $temp = $temp->withLink(new Link('self', '/baz/XXXX-' . $i));
            $temp = $temp->withLink(new Link('doc', '/doc/baz'));
            $baz[] = $temp;
        }

        $resource = $resource->embed('bar', $bar);
        $resource = $resource->embed('baz', $baz);

        return $resource;
    }
}
