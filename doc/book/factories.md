# Provided factories

This component provides a number of factories for use with
[PSR-11](http://www.php-fig.org/psr/psr-11/), in order to generate fully
configured instances for your use.

## Hal\HalResponseFactoryFactory

- Registered as service: `Hal\HalResponseFactory`
- Generates instance of: `Hal\HalResponseFactory`
- Depends on:
    - [zend-diactoros](https://docs.zendframework.com/zend-diactoros/), as it uses
      that library for the response prototype and stream generator.

If you want to use a different PSR-7 implementation for the response and stream,
create an alternate factory, and map it to the `Hal\HalResponseFactory` service.

## Hal\LinkGeneratorFactory

- Registered as service: `Hal\LinkGenerator`
- Generates instance of: `Hal\LinkGenerator`
- Depends on:
    - `Hal\LinkGenerator\UrlGenerator` service

## Hal\LinkGenerator\ExpressiveUrlGeneratorFactory

- Registered as service: `Hal\LinkGenerator\ExpressiveUrlGenerator`
- Generates instance of: `Hal\LinkGenerator\ExpressiveUrlGenerator`
- Depends on:
    - [zendframework/zend-expressive-helpers](https://github.com/zendframework/zend-expressive-helpers) package
    - `Zend\Expressive\Helper\UrlHelper` service
    - `Zend\Expressive\Helper\ServerUrlHelper` service (optional; if not provided,
      URIs will be generated without authority information)

## Hal\LinkGenerator\UrlGenerator

- Registered as service: `Hal\LinkGenerator\UrlGenerator`
- Aliased to service: `Hal\LinkGenerator\ExpressiveUrlGenerator`

You can either define an alternate alias, or map the `UrlGenerator` service
directly to a factory that will return a valid instance.

## Hal\Metadata\MetadataMapFactory

- Registered as service: `Hal\Metadata\MetadataMap`
- Generates instance of: `Hal\Metadata\MetadataMap`
- Depends on:
    - `config` service; if not present, will use an empty array

This service uses the `Hal\Metadata\MetadataMap` key of the `config` service in
order to configure and return a `Hal\Metadata\MetadataMap` instance. It expects
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

## Hal\ResourceGeneratorFactory

- Registered as service: `Hal\ResourceGenerator`
- Generates instance of: `Hal\ResourceGenerator`
- Depends on:
    - `Hal\Metadata\MetadataMap` service
    - `Zend\Hydrator\HydratorPluginManager` service
    - `Hal\LinkGenerator` service

If you wish to use a container implementation other than the
`Zend\Hydrator\HydratorPluginManager`, either register it under that service
name, or create an alternate factory.
