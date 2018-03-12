<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Create and return a HalResponseFactory instance.
 *
 * Utilizes the following services:
 *
 * - `Hal\Renderer\JsonRenderer`, if present; otherwise, creates an instance.
 * - `Hal\Renderer\XmlRenderer`, if present; otherwise, creates an instance.
 * - `Psr\Http\Message\ResponseInterface`, if present; otherwise, uses the
 *   zend-diactoros `Response` class.
 * - `Psr\Http\Message\StreamInterface`, if present; this service should
 *   return a callable capable of returning a new stream instance. If none is
 *   provided, uses a callable returning a zend-diactoros `Stream` class.
 */
class HalResponseFactoryFactory
{
    /**
     * @throws RuntimeException if neither a ResponseInterface service is
     *     present nor zend-diactoros is installed.
     */
    public function __invoke(ContainerInterface $container) : HalResponseFactory
    {
        $response = $this->getResponseInstance($container);
        $streamFactory = $this->getStreamFactory($container);

        $jsonRenderer = $container->has(Renderer\JsonRenderer::class)
            ? $container->get(Renderer\JsonRenderer::class)
            : new Renderer\JsonRenderer();

        $xmlRenderer = $container->has(Renderer\XmlRenderer::class)
            ? $container->get(Renderer\XmlRenderer::class)
            : new Renderer\XmlRenderer();

        return new HalResponseFactory(
            $response,
            $streamFactory,
            $jsonRenderer,
            $xmlRenderer
        );
    }

    /**
     * @throws RuntimeException if neither a ResponseInterface service is available
     *     nor zend-diactoros installed.
     */
    private function getResponseInstance(ContainerInterface $container) : ResponseInterface
    {
        if ($container->has(ResponseInterface::class)) {
            $response = $container->get(ResponseInterface::class);
            return is_callable($response) ? $response() : $response;
        }

        if (class_exists(Response::class)) {
            return new Response();
        }

        throw new RuntimeException(sprintf(
            'The %s implementation requires that you either define a service '
            . '"%s" or have zend-diactoros installed; either create %s service '
            . 'or install zendframework/zend-diactoros.',
            self::class,
            ResponseInterface::class,
            ResponseInterface::class
        ));
    }

    /**
     * @throws RuntimeException if neither a StreamInterface service is available
     *     nor zend-diactoros installed.
     */
    private function getStreamFactory(ContainerInterface $container) : callable
    {
        if ($container->has(StreamInterface::class)) {
            return $container->get(StreamInterface::class);
        }

        if (class_exists(Stream::class)) {
            return Closure::fromCallable([$this, 'generateStream']);
        }

        throw new RuntimeException(sprintf(
            'The %s implementation requires that you either define a service '
            . '"%s" (which should return a callable capable of returning a %s) '
            . 'or have zend-diactoros installed; either create %s service '
            . 'or install zendframework/zend-diactoros.',
            self::class,
            StreamInterface::class,
            StreamInterface::class,
            StreamInterface::class
        ));
    }

    private function generateStream() : Stream
    {
        return new Stream('php://temp', 'wb+');
    }
}
