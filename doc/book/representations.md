# Generating Representations

As noted in the [introduction](intro.md) examples, this component provides
`Hal\HalResponseFactory` for generating a PSR-7 response containing the HAL
representation. This chapter dives into that with more detail.

## Creating the factory

`HalResponseFactory` generates a PSR-7 response containing a representation of
the provided `HalResource` instance. In order to keep the component agnostic of
PSR-7 implement, the factory composes:

- JSON flags to use when generating a JSON representation.
- A PSR-7 response prototype.
- A callable capable of generating an empty, writable, PSR-7 stream instance.

As an example:

```php
use Hal\HalResponseFactory;
use Slim\Http\Response;
use Slim\Http\Stream;

$factory = new HalResponseFactory(
    HalResponseFactory::DEFAULT_JSON_FLAGS,
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

- Usage of the JSON flags `JSON_PRETTY_PRINT`, `JSON_UNESCAPED_SLASHES`,
  `JSON_UNESCAPED_UNICODE`, and `JSON_PRESERVE_ZERO_FRACTION`.
- Usage of `Zend\Diactoros\Response`.
- A callable that returns a new `Zend\Diactoros\Stream` using `php://temp` as
  its backing resource.

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
