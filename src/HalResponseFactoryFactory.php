<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Create and return a HalResponseFactory instance.
 *
 * Utilizes the following services:
 *
 * - `Psr\Http\Message\ResponseInterface`; must resolve to a PHP callable capable
 *   of producing an instance of that type.
 * - `Hal\Renderer\JsonRenderer`, if present; otherwise, creates an instance.
 * - `Hal\Renderer\XmlRenderer`, if present; otherwise, creates an instance.
 */
class HalResponseFactoryFactory
{
    /**
     * @throws RuntimeException if neither a ResponseInterface service is
     *     present nor zend-diactoros is installed.
     */
    public function __invoke(ContainerInterface $container) : HalResponseFactory
    {
        $jsonRenderer = $container->has(Renderer\JsonRenderer::class)
            ? $container->get(Renderer\JsonRenderer::class)
            : new Renderer\JsonRenderer();

        $xmlRenderer = $container->has(Renderer\XmlRenderer::class)
            ? $container->get(Renderer\XmlRenderer::class)
            : new Renderer\XmlRenderer();

        return new HalResponseFactory(
            $container->get(ResponseInterface::class),
            $jsonRenderer,
            $xmlRenderer
        );
    }
}
