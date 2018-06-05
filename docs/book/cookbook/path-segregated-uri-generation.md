# Using the ResourceGenerator in path-segregated middleware

- Since 1.1.0.

You may want to develop your API as a separate module that you can then drop in
to an existing application; you may even want to [path-segregate](https://docs.zendframework.com/zend-expressive/v3/features/router/piping/#path-segregation) it.

In such cases, you will want to use a different router instance, which has a
huge number of ramifications:

- You'll need separate routing middleware.
- You'll need a separate [UrlHelper](https://docs.zendframework.com/zend-expressive/v3/features/helpers/url-helper/) instance, as well as its related middleware.
- You'll need a separate URL generator for HAL that consumes the separate
  `UrlHelper` instance.
- You'll need a separate `LinkGenerator` for HAL that consumes the separate URL
  generator.
- You'll need a separate `ResourceGenerator` for HAL that consumes the separate
  `LinkGenerator`.

This can be accomplished by writing your own factories, but that means a lot of
extra code, and the potential for it to go out-of-sync with the official
factories for these services. What should you do?

## Virtual services

Since version 1.1.0 of this package, and versions 3.1.0 of
zend-expressive-router and 5.1.0 of zend-expressive-helpers, you can now pass
additional constructor arguments to a number of factories to allow varying the
service dependencies they look for.

In our example below, we will create an `Api` module. This module will have its
own router, and be segregated in the path `/api`; all routes we create will be
relative to that path, and not include it in their definitions. The handler we
create will return HAL-JSON, and thus need to generate links using the
configured router and base path.

To begin, we will alter the `ConfigProvider` for our module to add the
definitions noted below:

```php
// in src/Api/ConfigProvider.php:
namespace Api;

use Zend\Expressive\Hal\LinkGeneratorFactory;
use Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGeneratorFactory;
use Zend\Expressive\Hal\Metadata\MetadataMap;
use Zend\Expressive\Hal\ResourceGeneratorFactory;
use Zend\Expressive\Helper\UrlHelperFactory;
use Zend\Expressive\Helper\UrlHelperMiddlewareFactory;
use Zend\Expressive\Router\FastRouteRouter;
use Zend\Expressive\Router\Middleware\RouteMiddlewareFactory;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            MetadataMap::class => $this->getMetadataMap(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'factories' => [
                // module-specific class name => factory
                LinkGenerator::class          => new LinkGeneratorFactory(UrlGenerator::class),
                ResourceGenerator::class      => new ResourceGeneratorFactory(LinkGenerator::class),
                Router::class                 => FastRouteRouterFactory::class,
                UrlHelper::class              => new UrlHelperFactory('/api', Router::class),
                UrlHelperMiddleware::class    => new UrlHelperMiddlewareFactory(UrlHelper::class),
                UrlGenerator::class           => new ExpressiveUrlGeneratorFactory(UrlHelper::class),

                // Our handler:
                CreateBookHandler::class => CreateBookHandlerFactory::class,

                // And our pipeline:
                Pipeline::class => PipelineFactory::class,
            ],
        ];
    }

    public function getMetadataMap() : array
    {
        return [
            // ...
        ];
    }
}
```

Note that the majority of these service names are _virtual_; they do not resolve
to actual classes. PHP allows usage of the `::class` pseudo-constant anywhere,
and will resolve the value based on the current namespace. This gives us virtual
services such as `Api\Router`, `Api\UrlHelper`, etc.

Also note that we are creating factory _instances_. Normally, we recommend not
using closures or instances for factories due to potential problems with
configuration caching. Fortunately, we have provided functionality in each of
these factories that allows them to be safely cached, retaining the
context-specific configuration required.

> ### What about the hard-coded path?
>
> You'll note that the above example hard-codes the base path for the
> `UrlHelper`. What if you want to use a different path?
>
> You can override the service in an application-specific configuration under
> `config/autoload/`, specifying a different path!
>
> ```php
> \Api\UrlHelper::class => new UrlHelperFactory('/different/path', \Api\Router::class),
> ```

## Using virtual services with a handler

Now let's turn to our `CreateBookHandler`. We'll define it as follows:

```php
// in src/Api/CreateBookHandler.php:
namespace Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Hal\HalResponseFactory;
use Zend\Expressive\Hal\ResourceGenerator;

class CreateBookHandler implements RequestHandlerInterface
{
    private $resourceGenerator;

    private $responseFactory;

    public function __construct(ResourceGenerator $resourceGenerator, HalResponseFactory $responseFactory)
    {
        $this->resourceGenerator = $resourceGenerator;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        // do some work ...

        $resource = $this->resourceGenerator->fromObject($book, $request);
        return $this->responseFactory->createResponse($request, $book);
    }
}
```

This handler needs a HAL resource generator. More specifically, it needs the one
specific to our module. As such, we'll define our factory as follows:

```php
// in src/Api/CreateBookHandlerFactory.php:
namespace Api;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Hal\HalResponseFactory;

class CreateBookHandlerFactory
{
    public function __invoke(ContainerInterface $container) : CreateBookHandler
    {
        return new CreateBookHandler(
            ResourceGenerator::class, // module-specific service name!
            HalResponseFactory::class
        );
    }
}
```

You can create any number of such handlers for your module; the above
demonstrates how and where injection of the alternate resource generator occurs.

## Creating our pipeline and routes

Now we can create our pipeline and routes.

Generally when piping to an application instance, we can specify a class name of
middleware to pipe, or an array of middleware:

```php
// in config/pipeline.php:
$app->pipe('/api', [
    \Zend\ProblemDetails\ProblemDetailsMiddleware::class,
    \Api\RouteMiddleware::class,     // module-specific routing middleware!
    ImplicitHeadMiddleware::class,
    ImplicitOptionsMiddleware::class,
    MethodNotAllowedMiddleware::class,
    \Api\UrlHelperMiddleware::class, // module-specific URL helper middleware!
    DispatchMiddleware::class,
    \Zend\ProblemDetails\ProblemDetailsNotFoundHandler::class,
]);
```

However, we have both the pipeline _and_ routes, and we likely want to indicate
the exact behavior of this pipeline. Additionally, we may want to re-use this
pipeline in other applications; pushing this into the application configuration
makes that more error-prone.

As such, we will create a factory that generates and returns a
`Zend\Stratigility\MiddlewarePipe` instance that is fully configured for our
module. As part of this functionality, we will also add our module-specific
routing.

```php
// In src/Api/PipelineFactory.php:
namespace Api;

use Psr\Container\ContainerInterface;
use Zend\Expressive\MiddlewareFactory;
use Zend\Expressive\Router\Middleware as RouterMiddleware;
use Zend\ProblemDetails\ProblemDetailsMiddleware;
use Zend\ProblemDetails\ProblemDetailsNotFoundHandler;
use Zend\Stratigility\MiddlewarePipe;

class PipelineFactory
{
    public function __invoke(ContainerInterface $container) : MiddlewarePipe
    {
        $factory = $container->get(MiddlewareFactory::class);

        // First, create our middleware pipeline
        $pipeline = new MiddlewarePipe();
        $pipeline->pipe($factory->lazy(ProblemDetailsMiddleware::class));
        $pipeline->pipe($factory->lazy(RouteMiddleware::class)); // module-specific!
        $pipeline->pipe($factory->lazy(RouterMiddleware\ImplicitHeadMiddleware::class));
        $pipeline->pipe($factory->lazy(RouterMiddleware\ImplicitOptionsMiddleware::class));
        $pipeline->pipe($factory->lazy(RouterMiddleware\MethodNotAllowedMiddleware::class));
        $pipeline->pipe($factory->lazy(UrlHelperMiddlweare::class)); // module-specific!
        $pipeline->pipe($factory->lazy(RouterMiddleware\DispatchMiddleware::class));
        $pipeline->pipe($factory->lazy(ProblemDetailsNotFoundHandler::class));

        // Second, we'll create our routes
        $router = $container->get(Router::class); // Retrieve our module-specific router
        $routes = new RouteCollector($router);    // Create a route collector to simplify routing

        // Start routing:
        $routes->post('/books', $factory->lazy(CreateBookHandler::class));

        // Return the pipeline now that we're done!
        return $pipeline;
    }
}
```

Note that the routing definitions do **not** include the prefix `/api`; this is
because that prefix will be stripped when we path-segregate our API middleware
pipeline. All routing will be _relative_ to that path.

## Creating a path-segregated pipeline

Finally, we will attach our pipeline to the application, using path segregation:

```php
// in config/pipeline.php:
$app->pipe('/api', \Api\Pipeline::class);
```

This statement tells the application to pipe the pipeline returned by our
`PipelineFactory` under the path `/api`; that path will be stripped from
requests when passed to the underlying middleware.

At this point, we now have a re-usable module, complete with its own routing,
with URI generation that will include the base path under which we have
segregated the pipeline!
