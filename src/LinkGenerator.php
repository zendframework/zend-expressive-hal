<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use Psr\Http\Message\ServerRequestInterface;

class LinkGenerator
{
    /**
     * @var LinkGenerator\UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(LinkGenerator\UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function fromRoute(
        string $relation,
        ServerRequestInterface $request,
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        array $attributes = []
    ) : Link {
        return new Link($relation, $this->urlGenerator->generate(
            $request,
            $routeName,
            $routeParams,
            $queryParams
        ), false, $attributes);
    }

    /**
     * Creates a templated link
     */
    public function templatedFromRoute(
        string $relation,
        ServerRequestInterface $request,
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        array $attributes = []
    ) : Link {
        return new Link($relation, $this->urlGenerator->generate(
            $request,
            $routeName,
            $routeParams,
            $queryParams
        ), true, $attributes);
    }
}
