<?php

namespace Hal;

use Psr\Http\Message\ServerRequestInterface;

class LinkGenerator
{
    /**
     * @var LinkGenerator\UrlGenerator
     */
    private $urlGenerator;

    public function __construct(LinkGenerator\UrlGenerator $urlGenerator)
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
