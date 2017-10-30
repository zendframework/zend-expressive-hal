<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\LinkGenerator;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

class ExpressiveUrlGenerator implements UrlGeneratorInterface
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
