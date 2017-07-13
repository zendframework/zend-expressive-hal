# Generating Representations

This component provides two renderers, one each for creating JSON and XML
payloads.

Additionally, as noted in the [introduction](intro.md) examples, this component
provides `Hal\HalResponseFactory` for generating a PSR-7 response containing the
HAL representation. This chapter dives into that with more detail.

## Renderers

All renderers implement `Hal\Renderer\Renderer`:

```php
namespace Hal\Renderer;

use Hal\HalResource;

interface Renderer
{
    public function render(HalResource $resource) : string;
}
```

Two implementations are provided, `Hal\Renderer\JsonRenderer` and
`Hal\Renderer\XmlRenderer`

### JsonRenderer

The `JsonRenderer` constructor allows you to specify a bitmask of flags for use
with `json_encode()`. By default, if none are provided, it uses the value of
`JsonRenderer::DEFAULT_JSON_FLAGS`, which evaluates to:

```php
JSON_PRETTY_PRINT
| JSON_UNESCAPED_SLASHES
| JSON_UNESCAPED_UNICODE
| JSON_PRESERVE_ZERO_FRACTION
```

This provides human-readable JSON output.

### XmlRenderer

The `XmlRenderer` produces XML representations of HAL resources. It has no
constructor arguments at this time.

## HalResponseFactory

`HalResponseFactory` generates a PSR-7 response containing a representation of
the provided `HalResource` instance. In order to keep the component agnostic of
PSR-7 implementation, the factory composes:

- A PSR-7 response prototype. A zend-diactoros `Response` is used if none is
  provided.
- A callable capable of generating an empty, writable, PSR-7 stream instance.
  If none is provided, a callable returning a zend-diactoros `Stream` is
  provided.

As an example:

```php
use Hal\HalResponseFactory;
use Slim\Http\Response;
use Slim\Http\Stream;

$factory = new HalResponseFactory(
    new Response(),
    function () {
        return new Stream(fopen('php://temp', 'wb+'));
    }
);
```

> ### Streams
>
> A factory callable is necessary for generating streams as they are usually
> backed by PHP resources, which are not immutable. Sharing instances could
> thus potentially lead to appending or overwriting contents!

By default, if you pass no arguments to the `HalResponseFactory` constructor, it
assumes the following:

- Usage of `Zend\Diactoros\Response`.
- A callable that returns a new `Zend\Diactoros\Stream` using `php://temp` as
  its backing resource.
- A `JsonRenderer` instance is created if none is provided.
- An `XmlRenderer` instance is created if none is provided.

We provide a PSR-11 compatible factory for generating the `HalResponseFactory`
which uses zend-diactoros by default.

## Using the factory

The factory exposes one method:

```php
use Hal\HalResource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
