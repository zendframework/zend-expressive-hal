# Generating Representations

This component provides two renderers, one each for creating JSON and XML
payloads.

Additionally, as noted in the [introduction](intro.md) examples, this component
provides `Zend\Expressive\Hal\HalResponseFactory` for generating a
[PSR-7](https://www.php-fig.org/psr/psr-7/) response containing the HAL
representation. This chapter dives into that with more detail.

## Renderers

All renderers implement `Zend\Expressive\Hal\Renderer\RendererInterface`:

```php
namespace Zend\Expressive\Hal\Renderer;

use Zend\Expressive\Hal\HalResource;

interface RendererInterface
{
    public function render(HalResource $resource) : string;
}
```

Two implementations are provided, `Zend\Expressive\Hal\Renderer\JsonRenderer` and
`Zend\Expressive\Hal\Renderer\XmlRenderer`

### JsonRenderer

The `JsonRenderer` constructor allows you to specify a bitmask of flags for use
with `json_encode()`. By default, if none are provided, it uses the value of
`JsonRenderer::DEFAULT_JSON_FLAGS`, which evaluates to:

```php
JSON_UNESCAPED_SLASHES
| JSON_UNESCAPED_UNICODE
| JSON_PRESERVE_ZERO_FRACTION
```

When your application is in "debug" mode, it also adds the `JSON_PRETTY_PRINT`
flag to the default list, in order to provide human-readable JSON output.

### XmlRenderer

The `XmlRenderer` produces XML representations of HAL resources. It has no
constructor arguments at this time.

## HalResponseFactory

`HalResponseFactory` generates a PSR-7 response containing a representation of
the provided `HalResource` instance. In order to keep the component agnostic of
PSR-7 implementation, `HalResponseFactory` itself composes a callable factory
capable of producing an empty PSR-7 response.

As an example:

```php
use Slim\Http\Response;
use Slim\Http\Stream;
use Zend\Expressive\Hal\HalResponseFactory;

$factory = new HalResponseFactory(
    function () {
        return new Response();
    }
);
```

Additionally, the `HalResponseFactory` constructor can accept the following
arguments, with the described defaults if none is provided:

- A `JsonRenderer` instance is created if none is provided.
- An `XmlRenderer` instance is created if none is provided.

We provide a [PSR-11](https://www.php-fig.org/psr/psr-11) compatible factory for
generating the `HalResponseFactory`, described in [the factories
chapter](factories.md#zendexpressivehalhalresponsefactoryfactory).

## Using the factory

The factory exposes one method:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\HalResource;

public function createResponse(
    ServerRequestInterface $request,
    HalResource $resource,
    string $mediaType = self::DEFAULT_CONTENT_TYPE
) : ResponseInterface {
```

Generally speaking, you'll pass the current request instance, and the resource
for which you want to generate a response, and the factory will return a
response based on its response prototype, with the following:

- A `Content-Type` header with the base media type of `application/hal`.
- A message body containing the representation.

The request instance is used to determine what representation to create, based
on the `Accept` header. If it matches a JSON media type, a JSON representation
is created, and the `Content-Type` will be appended with `+json`; for XML, an
XML representation is created, and the `Content-Type` will be appended with
`+xml`. If no media type is matched, XML is generated.

One practice often used is to provide a _custom media type_ for your
representations. While they will still be HAL, this allows you to document the
specific structure of your resources, and potentially even validate them against
JSON schema.

To do this, pass the media type when creating the response:

```php
$response = $factory->createResponse(
    $request,
    $resource,
    'application/vnd.book'
);
```

_Do not_ pass the format (e.g., `+json`, `+xml`) when doing so; the factory will
append the appropriate one based on content negotiation.

## Forcing collections for relations

HAL allows links and embedded resources to be represented as:

- a single object
- an array of objects of the same type

Internally, this package checks to see if only one of the item exists, and, if
so, it will render it by itself. However, there are times you may want to force
an array representation. As an example, if your resource models a car, and you
have a `wheels` relation, it would not make sense to return a single wheel, even
if that's all the car currently has associated with it.

To accommodate this, we provide two features.

For links, you may pass a special attribute, `Zend\Expressive\Hal\Link::AS_COLLECTION`,
with a boolean value of `true`; when encountered, this will then be rendered as
an array of links, even if only one link for that relation is present.

```php
$link = new Link(
    'wheels',
    '/api/car/XXXX-YYYY-ZZZZ/wheels/111',
    false,
    [Link::AS_COLLECTION => true]
);

$resource = $resource->withLink($link);
```

In the above, you will then get the following within your representation:

```json
"_links": {
  "wheels": [
    {"href": "/api/car/XXXX-YYYY-ZZZZ/wheels/111"}
  ]
}
```

To force an embedded resource to be rendered within an array, you have two
options.

First, and simplest, pass the resource within an array when calling
`withElement()`, `embed()`, or passing data to the constructor:

```php
// Constructor:
$resource = new HalResource(['wheels' => [$wheel]]);

// withElement():
$resource = $resource->withElement('wheels', [$wheel]);

// embed():
$resource = $resource->embed('wheels', [$wheel]);
```

Alternately, you can call the `HalResource::embed` method with only the
resource, passing the method a third argument, a flag indicating whether or not
to force an array:

```php
$resource = $resource->embed('wheels', $wheel, true);
```

In each of these cases, assuming no other wheels were provided to the final
resource, you might get a representation such as the following:

```json
"_embedded": {
  "wheels": [
    {
      "_links" => {"self": {"href": "..."}}
      "id": "..."
    },
  ]
}
```
