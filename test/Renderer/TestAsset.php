<?php

namespace HalTest\Renderer;

use Hal\HalResource;
use Hal\Link;

trait TestAsset
{
    public function createExampleResource() : HalResource
    {
        $resource = new HalResource([
            'id'      => 'XXXX-YYYY-ZZZZ-ABAB',
            'example' => true,
            'foo'     => 'bar',
            'list'     => [1, 2, 3]
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
