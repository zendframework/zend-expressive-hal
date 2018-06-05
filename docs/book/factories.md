# Provided factories

This component provides a number of factories for use with
[PSR-11](https://www.php-fig.org/psr/psr-11/), in order to generate fully
configured instances for your use.

## Zend\Expressive\Hal\HalResponseFactoryFactory

- Registered as service: `Zend\Expressive\Hal\HalResponseFactory`
- Generates instance of: `Zend\Expressive\Hal\HalResponseFactory`
- Depends on:
    - `Psr\Http\Message\ResponseInterface` service. The service must resolve to
      a PHP callable capable of generating a [PSR-7](https://www.php-fig.org/psr/psr-7/)
      `ResponseInterface` instance; it must not resolve to a `ResponseInterface`
      instance directly. This service is **required**, and must be supplied by
      the application. If you are using with zend-expressive v3 and above, the
      service will already be registered.
    - `Zend\Expressive\Hal\Renderer\JsonRenderer` service. If the service is not
      present, it instantiates an instance itself.
    - `Zend\Expressive\Hal\Renderer\XmlRenderer` service. If the service is not
      present, it instantiates an instance itself.

## Zend\Expressive\Hal\LinkGeneratorFactory

- Registered as service: `Zend\Expressive\Hal\LinkGenerator`
- Generates instance of: `Zend\Expressive\Hal\LinkGenerator`
- Depends on:
    - `Zend\Expressive\Hal\LinkGenerator\UrlGeneratorInterface` service

Since version 1.1.0, this factory allows an optional constructor argument,
`$urlGeneratorServiceName`. It defaults to
`Zend\Expressive\Hal\LinkGenerator\UrlGeneratorInterface`,
but you may specify an alternate service if desired. This may be useful, for
instance, when using an alternate router in a path-segregated middleware
pipeline, which would necessitate a different `UrlHelper` instance, and an
alternate URL generator that consumes it.

## Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGeneratorFactory

- Registered as service: `Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGenerator`
- Generates instance of: `Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGenerator`
- Depends on:
    - [zendframework/zend-expressive-helpers](https://github.com/zendframework/zend-expressive-helpers) package
    - `Zend\Expressive\Helper\UrlHelper` service
    - `Zend\Expressive\Helper\ServerUrlHelper` service (optional; if not provided,
      URIs will be generated without authority information)

Since version 1.1.0, this factory allows an optional constructor argument, `$urlHelperServiceName`.
It defaults to `Zend\Expressive\Helper\UrlHelper`, but you may specify an
alternate service if desired. This may be useful, for instance, when using an
alternate router in a path-segregated middleware pipeline, which would
necessitate a different `UrlHelper` instance.

## Zend\Expressive\Hal\LinkGenerator\UrlGeneratorInterface

- Registered as service: `Zend\Expressive\Hal\LinkGenerator\UrlGeneratorInterface`
- Aliased to service: `Zend\Expressive\Hal\LinkGenerator\ExpressiveUrlGenerator`

You can either define an alternate alias, or map the `UrlGeneratorInterface` service
directly to a factory that will return a valid instance.

## Zend\Expressive\Hal\Metadata\MetadataMapFactory

- Registered as service: `Zend\Expressive\Hal\Metadata\MetadataMap`
- Generates instance of: `Zend\Expressive\Hal\Metadata\MetadataMap`
- Depends on:
    - `config` service; if not present, will use an empty array

This service uses the `Zend\Expressive\Hal\Metadata\MetadataMap` key of the `config` service in
order to configure and return a `Zend\Expressive\Hal\Metadata\MetadataMap` instance. It expects
that value to be an array of elements, each with the following structure:

```php
[
    '__class__' => 'Fully qualified class name of an AbstractMetadata type',
    // additional key/value pairs as required by the metadata type.
]
```

The additional pairs are as follows:

- For `UrlBasedResourceMetadata`:
    - `resource_class`: the resource class the metadata describes.
    - `url`: the URL to use when generating a self-relational link for the
      resource.
    - `extractor`: the extractor/hydrator service to use to extract resource
      data.
- For `UrlBasedCollectionMetadata`:
    - `collection_class`: the collection class the metadata describes.
    - `collection_relation`: the embedded relation for the collection in the
      generated resource.
    - `url`: the URL to use when generating a self-relational link for the
      collection resource.
    - `pagination_param`: the name of the parameter indicating what page of data
      is present. Defaults to "page".
    - `pagination_param_type`: whether the pagination parameter is a query string
      or path placeholder; use either `AbstractCollectionMetadata::TYPE_QUERY`
      ("query") or `AbstractCollectionMetadata::TYPE_PLACEHOLDER` ("placeholder");
      default is "query".
- For `RouteBasedResourceMetadata`:
    - `resource_class`: the resource class the metadata describes.
    - `route`: the route to use when generating a self relational link for the
      resource.
    - `extractor`: the extractor/hydrator service to use to extract resource
      data.
    - `resource_identifier`: what property in the resource represents its
      identifier; defaults to "id".
    - `route_identifier_placeholder`: what placeholder in the route string
      represents the resource identifier; defaults to "id".
    - `route_params`: an array of additional routing parameters to use when
      generating the self relational link for the resource.
- For `RouteBasedCollectionMetadata`:
    - `collection_class`: the collection class the metadata describes.
    - `collection_relation`: the embedded relation for the collection in the
      generated resource.
    - `route`: the route to use when generating a self relational link for the
      collection resource.
    - `pagination_param`: the name of the parameter indicating what page of data
      is present. Defaults to "page".
    - `pagination_param_type`: whether the pagination parameter is a query string
      or path placeholder; use either `AbstractCollectionMetadata::TYPE_QUERY`
      ("query") or `AbstractCollectionMetadata::TYPE_PLACEHOLDER` ("placeholder");
      default is "query".
    - `route_params`: an array of additional routing parameters to use when
      generating the self relational link for the collection resource. Defaults
      to an empty array.
    - `query_string_arguments`: an array of query string parameters to include
      when generating the self relational link for the collection resource.
      Defaults to an empty array.

If you have created custom metadata types, you can extend this class to
support them. Create `create<type>(array $metadata)` methods for each
type you wish to support, where `<type>` is your custom class name, minus
the namespace.

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

## Zend\Expressive\Hal\ResourceGeneratorFactory

- Registered as service: `Zend\Expressive\Hal\ResourceGenerator`
- Generates instance of: `Zend\Expressive\Hal\ResourceGenerator`
- Depends on:
    - `Zend\Expressive\Hal\Metadata\MetadataMap` service
    - `Zend\Hydrator\HydratorPluginManager` service
    - `Zend\Expressive\Hal\LinkGenerator` service

If you wish to use a container implementation other than the
`Zend\Hydrator\HydratorPluginManager`, either register it under that service
name, or create an alternate factory.

Since version 1.1.0, this factory allows an optional constructor argument, `$linkGeneratorServiceName`.
It defaults to `Zend\Expressive\Hal\LinkGenerator`, but you may specify an
alternate service if desired. This may be useful, for instance, when using an
alternate router in a path-segregated middleware pipeline, which would
necessitate a different `UrlHelper` instance, an alternate URL generator that
consumes it, and an alternate `LinkGenerator` consuming the URL generator.
