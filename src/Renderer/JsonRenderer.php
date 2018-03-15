<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Renderer;

use Zend\Expressive\Hal\HalResource;

use function json_encode;

class JsonRenderer implements RendererInterface
{
    // @codingStandardsIgnoreStart
    /**
     * @var int Default flags to use with json_encode()
     */
    const DEFAULT_JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;
    // @codingStandardsIgnoreEnd

    /** @var int */
    private $jsonFlags;

    public function __construct(int $jsonFlags = self::DEFAULT_JSON_FLAGS)
    {
        $this->jsonFlags = $jsonFlags;
    }

    public function render(HalResource $resource) : string
    {
        return json_encode($resource, $this->jsonFlags);
    }
}
