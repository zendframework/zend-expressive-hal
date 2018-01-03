# Links and Resources

The basic building blocks of this component are links and resources:

- `Zend\Expressive\Hal\Link`
- `Zend\Expressive\Hal\HalResource`

> ### Note on naming
>
> Why `HalResource` and not the simpler `Resource`? The answer: PHP. As of PHP
> 7, `resource` has been designated a potential future language keyword. In
> order to be forwards compatible, we opted to name our class `HalResource`.

> ### PSR-13
> 
> zendframework/zend-expressive-hal implements [PSR-13](http://www.php-fig.org/psr/psr-13/),
> which provides interfaces for relational links and collections of relational
> links. `Zend\Expressive\Hal\Link` implements `Psr\Link\EvolvableLinkInterface`, and
> `Zend\Expressive\Hal\HalResource` implements `Psr\Link\EvolvableLinkProviderInterface`.

Resources compose links, so we'll cover links first.

## Links

Links provide URIs to related resources.

Any given link, therefore, needs to compose the _relation_ and a _URI_.
Additionally, links:

- can be _templated_. Templated links have one or more `{variable}` placeholders
  in them that clients can then fill in in order to generate a fully qualified
  URI.
- can contain a number of other attributes: type, title, name, etc.

To model these, we provide the `Zend\Expressive\Hal\Link` class. It has the
following constructor:

```php
public function __construct(
    $relation,
    string $uri = '',
    bool $isTemplated = false,
    array $attributes = []
)
```

`$relation` can be a string value, or an array of string values, representing
the relation.

To access these various properties, you can use the following methods:

```php
$link->getRels()       // get the list of relations for the link
$link->getHref()       // retrieve the URI
$link->isTemplated()   // is the link templated?
$link->getAttributes() // get any additional link attributes
```

A `Link` is _immutable_; you cannot change it after the fact. If you need a
modified version of the link, we provide several methods that will return _new
instances_ containing the changes:

```php
$link = $link->withRel($rel);    // or provide an array of relations
$link = $link->withoutRel($rel); // or provide an array of relations
$link = $link->withHref($href);
$link = $link->withAttribute($attribute, $value);
$link = $link->withoutAttribute($attribute);
```

With these tools, you can describe any relational link.

### Route-based link URIs

Most frameworks provide routing capabilities, and delegate URI generation to
their routers to ensure that generated links conform to known routing
specifications. `Link` expects only a string URI, however; how can you prevent
hard-coding that URI?

This component provides a tool for that: `Zend\Expressive\Hal\LinkGenerator`.
This class composes a `Zend\Expressive\Hal\LinkGenerator\UrlGeneratorInterface`
instance, which defines the following:

```php
namespace Zend\Expressive\Hal\LinkGenerator;

use Psr\Http\Message\ServerRequestInterface;

interface UrlGeneratorInterface
{
    /**
     * Generate a URL for use as the HREF of a link.
     *
     * - The request is provided, to allow the implementation to pull any
     *   request-specific items it may need (e.g., results of routing, original
     *   URI for purposes of generating a fully-qualified URI, etc.).
     *
     * - `$routeParams` are any replacements to make in the route string.
     *
     * - `$queryParams` are any query string parameters to include in the
     *   generated URL.
     */
    public function generate(
        ServerRequestInterface $request,
        string $routeName,
        array $routeParams = [],
        array $queryParams = []
    ) : string;
}
```

We provide a default implementation for Expressive users,
`Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGenerator`,  that uses the
`UrlHelper` and `ServerUrlHelper` from zend-expressive-helpers in order to
generate URIs.

The `LinkGenerator` itself defines two methods:

```php
$link = $linkGenerator->fromRoute(
    $relation,
    $request,
    $routeName,
    $routeParams, // Array of additional route parameters to inject
    $queryParams, // Array of query string arguments to inject
    $attributes   // Array of Link attributes to use
);

$link = $linkGenerator->templatedFromRoute(
    $relation,
    $request,
    $routeName,
    $routeParams, // Array of additional route parameters to inject
    $queryParams, // Array of query string arguments to inject
    $attributes   // Array of Link attributes to use
);
```

`fromRoute()` will generate a non-templated `Link` instance, while
`templatedFromRoute()` generates a templated instance.

If you need to generate custom links based on routing, we recommend composing
the `LinkGenerator` in your own classes to do so.

> ### Limitation
> 
> There is a [known limitation](https://github.com/zendframework/zend-expressive-hal/issues/5)
> with zend-router when using routes with optional parameters (e.g., `/books[/:id]`,
> where `:id` is optional). In such cases, if no matching parameter is provided
> (such as when generating a URI without an `:id`), the router will raise an
> exception due to the missing parameter.
> 
> If you encounter this issue, create separate routing entries for each optional
> parameter. See the issue for a comprehensive example.

## Resources

A HAL resource is simply the representation you want to return for your API.
`Zend\Expressive\Hal\HalResource` allows you to model these representations,
along with any relational links and child resources.

It defines the following constructor:

```php
public function __construct(
    array $data = [],
    array $links = [],
    array $embedded = []
)
```

`$data` should be an associative array of data you wish to include in your
representation; the only limitation is you may not use the keys `_links` or
`_embedded`, as these are reserved keywords.

`$links` should be an array of `Zend\Expressive\Hal\Link` instances.

`$embedded` should be an array of `Zend\Expressive\Hal\HalResource` instances.
Most often, however, you will include these with `$data`, as the class contains
logic for identifying them.

Once you have created an instance, you can access its properties:

```php
$resource->getElement($name) // retrieve an element or embedded resource by name
$resource->getElements()     // retrieve all elements and embedded resources
$resource->getLinks()        // retrieve all relational links
$resource->getLinksByRel()   // retrieve links for a specific relation
$resource->toArray()         // retrieve associative array representation
```

`HalResource` instances are _immutable_. We provide a number of methods that
allow you to create _new instances_ with changes:

```php
$resource = $resource->withElement($name, $value);
$resource = $resource->withoutElement($name);
$resource = $resource->withElements($elements);
$resource = $resource->embed($name, $resource);
$resource = $resource->withLink($link);
$resource = $resource->withoutLink($link);
```

With these tools, you can describe any resource you want to represent.
