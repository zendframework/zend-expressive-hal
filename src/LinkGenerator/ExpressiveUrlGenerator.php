<?php

namespace Hal\LinkGenerator;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

class ExpressiveUrlGenerator implements UrlGenerator
{
    /**
     * @var null|ServerUrlHelper
     */
    private $serverUrlHelper;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    public function __construct(UrlHelper $urlHelper, ServerUrlHelper $serverUrlHelper = null)
    {
        $this->urlHelper = $urlHelper;
        $this->serverUrlHelper = $serverUrlHelper;
    }

    public function generate(
        ServerRequestInterface $request,
        string $routeName,
        array $routeParams = [],
        array $queryParams = []
    ) : string {
        $path = $this->urlHelper->generate($routeName, $routeParams, $queryParams);

        if (! $this->serverUrlHelper) {
            return $path;
        }

        $serverUrlHelper = clone $this->serverUrlHelper;
        $serverUrlHelper->setUri($request->getUri());
        return $serverUrlHelper($path);
    }
}
