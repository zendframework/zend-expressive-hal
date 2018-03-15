<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Hal;

use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;

use function array_shift;
use function count;
use function get_class;
use function gettype;
use function in_array;
use function is_object;
use function sprintf;
use function var_export;

trait Assertions
{
    public static function getLinkByRel(string $rel, HalResource $resource) : Link
    {
        $links = $resource->getLinksByRel($rel);
        self::assertInternalType('array', $links, sprintf("Did not receive list of links for rel %s", $rel));
        self::assertCount(1, $links, sprintf(
            'Received more links than expected (expected 1; received %d) for rel %s',
            count($links),
            $rel
        ));
        return array_shift($links);
    }

    public static function assertLink(string $expectedRel, string $expectedHref, $actual) : void
    {
        self::assertThat($actual instanceof Link, self::isTrue(), sprintf(
            'Invalid link encountered of type %s',
            is_object($actual) ? get_class($actual) : gettype($actual)
        ));

        self::assertThat(in_array($expectedRel, $actual->getRels(), true), self::isTrue(), sprintf(
            'Failed asserting that link has relation %s; received %s',
            $expectedRel,
            var_export($actual->getRels(), true)
        ));

        self::assertThat($expectedHref === $actual->getHref(), self::isTrue(), sprintf(
            'Failed asserting that link defines HREF %s; received %s',
            $expectedHref,
            $actual->getHref()
        ));
    }
}
