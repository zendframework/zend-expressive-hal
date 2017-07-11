<?php

namespace Hal;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class HalResponseFactoryFactory
{
    /**
     * @throws RuntimeException if zend-diactoros is not installed.
     */
    public function __invoke(ContainerInterface $container) : HalResponseFactory
    {
        if (! class_exists(Response::class)) {
            throw new RuntimeException(sprintf(
                'The %s implementation requires zend-diactoros; either install '
                . 'zendframework/zend-diactoros, or create an alternate factory.',
                self::class
            ));
        }

        return new HalResponseFactory(
            HalResponseFactory::DEFAULT_JSON_FLAGS,
            new Response(),
            Closure::fromCallable([$this, 'generateStream'])
        );
    }

    public function generateStream() : Stream
    {
        return new Stream('php://temp', 'wb+');
    }
}
