# DESIGN

## Background

Hypertext Application Language, or HAL, is defined in two IETF proposals, [JSON
Hypertext Application Language](https://tools.ietf.org/html/draft-kelly-json-hal-08)
and [XML Hypertext Application Language](https://tools.ietf.org/html/draft-michaud-xml-hal-01).
The basic ideas behind each are:

- Provide generic, extensible resource representations for APIs.
- Provide standard mechanisms for such representations to provide hypermedia
  controls, or links.
- Allow resources to embed other related resources.

These three features allow providing generic payloads with relatively standard
structure from APIs.

We have previously provided an implementation via our [zf-hal Apigility
module](https://github.com/zfcampus/zf-hal). This implementation is tied
inextrictably to both zend-mvc and zend-view, making it impossible to re-use
within Expressive, or, more generally, PSR-7 middleware applications.

While other JSON and XML representations exist, such as
[Collection+JSON](http://amundsen.com/media-types/collection/format/) and
[json:api](http://jsonapi.org), we feel HAL is a viable option for APIs due to
its simplicity, predictability, and extensibility. In terms of Apigility on
Expressive, we also feel providing this format initially will help keep the new
project familiar to existing Apigility users.

This RFC proposes an architecture for how HAL support will be offered by Zend
Framework for consumption specifically in Expressive, and potentially within the
existing Apigility v1 project.

## Goals

- **Provide a usable API around creation of HAL resources and links, with only the
  minimum functionality necessary to create full resource and link
  representations.**
- Use relevant standards whenever possible:
  - [PSR-7](http://www.php-fig.org/psr/psr-7/) (HTTP Messages) for pulling
    request data such as the URI, `Accept` header, and, under Expressive,
    `RouteResult`), and generating responses.
  - [PSR-13](http://www.php-fig.org/psr/psr-13/) (Links) for representing
    relational links
  - [PSR-11](http://www.php-fig.org/psr/psr-11/) (Container) for providing
    factories for use with dependency injection containers.
- Standalone usage of links and resources that do not require additional
  components or features beyond packages defining standard interfaces. While
  value extraction and dynamic link generation are required features, they also
  bind the component to other components and, potentially, frameworks. **The core
  functionality should be usable anywhere.**
- Immutability. A resource should not change over time; changes represent new
  resources, which allow equality comparisons.
- Opt-in features for generating resources from objects. These should allow
  identifying collections (vs standalone resources), embedding resources, and
  providing default link sets.
- Opt-in features for paginating resource collections.
- Opt-in features for auto-generating `self` relational URIs based on routes.
- **Provide the ability to generate both JSON and XML representations.**
- Use 3rd party components, including ZF components, where possible, to
  implement everything from core functionality to opt-in functionality.

## Links

One basic tenet of HAL is facilitating
[HATEOAS](https://en.wikipedia.org/wiki/HATEOAS), by providing _links_ to other,
related resources and/or actions to perform. This is accomplished by the
`_links` reserved pseudo-element in the JSON version of the spec, and the
`<link>` reserved element in the XML version of the spec.

Within the PHP ecosystem, PSR-13 addresses the idea of links and collections of
links. This library should provide a `LinkInterface` and/or
`EvolvableLinkInterface` implementation.

```php
$link = new Link($rel, $uri, $isTemplated);
$link = $link->withAttribute('title', 'Book');
```

### CURIEs

CURIE stands for "Compact URI", and is somewhat analogous to XML namespaces: you
link a CURIE namespace to a templated URI, and then other links will use that
namespace as part of their relation, and their URI will be relative to the
templated URI. As an example:

```json
"_links": {
  "curies": [
    {
      "name": "doc",
      "href": "https://example.com/api/doc/{rel}",
      "templated": true
    },
    {
      "name": "book",
      "href": "https://example.com/api/book/{rel}",
      "templated": true
    }
  ],
  "doc:book": {
    "href": "/book"
  },
  "book:author": {
    "href": "/{book_id}/author",
    "templated": true
  },
},
"book_id": "XXXX-YYYY-ZZZZ"
```

In the above cases, two CURIE is defined, one with the namespace `doc`, and
another with the namespace `book`. Our two other links
expand on each of these, and ultimately resolve to:

- `doc:book`: `https://example.com/api/doc/book`
- `book:author`: `https://example.com/api/book/XXXX-YYYY-ZZZZ/author`

Interestingly, CURIE support can be expressed already in terms of PSR-13
interfaces.

To create a CURIE link, we create a normal `LinkInterface` instance with the
relation `curies` which is _templated_, and where the `href` contains the string
`{rel}`; this is the template that is expanded by a CURIE'd relational link.
Finally, we ensure the link has an attribute `name` which is the namespace for
any CURIE links we create.

We then add such a link to a `LinkProviderInterface` instance. If we have
multiple links with `curies` relations, these are aggregated.

Finally, we can then add links where the relation is `<CURIE
namespace>:<relation>`; when encountered, clients are expected to look up the
namespace amongst the `curies` links, and then replace the `{rel}` template with
the `href` of the link.

## Link generator

Generating URLs from routes, however, requires access to either the router or
the `UrlHelper`. One possibility is to have a `LinkGenerator` class that
composes the `UrlHelper` (and optionally `ServerUrlHelper` in order to provide
fully-qualified URIs), and provides a factory method for generating a link:

```php
$generator = new LinkGenerator(
    $container->get(UrlHelper::class),
    $container->get(ServerUrlHelper::class) // Optional
);

// Optional; proxies to UrlHelper::setRouteResult() to allow re-use of matched
// parameters.
$generator->setRouteResult($request->getAttribute(RouteResult::class));

// Optional; proxies to ServerUrlHelper::setUri()
$generator->setUri($request->getUri());

$link = $generator->fromRoute($relation, $route, $routeParams, $queryParams);
```

(In the last line, the last two arguments would be optional.)

### Dependencies

Any such class would need to be in an Expressive-specific bridge package, due to
its reliance on the zend-expressive-helpers classes.

Alternately, the package could define interfaces for `UrlHelperInterface` and
`ServerUrlHelperInterface` that define the `generate()` methods as they are
defined in zend-expressive-helpers. The package could then _optionally_ depend
on zend-expressive-helpers, and provide "implementations" like:

```php
namespace Hal\Link\Helper;

use Zend\Expressive\Helper\UrlHelper as ExpressiveUrlHelper;

class UrlHelper extends ExpressiveUrlHelper implements UrlHelperInterface
{
}
```

This would also require the package to override the `UrlHelper` and
`ServerUrlHelper` services, and provide alternate factories for each.

This approach would make it possible to keep the link and link generation
functionality within the core library, and allow users to create their own
implementations if they want to use the functionality outside the Expressive
ecosystem.

## Resources

A `Resource` will implement the PSR-13 `EvolvableLinkCollection` interface,
and is intended to be immutable once created.

However, to allow users to _evolve_ the resource — e.g., to add data that may
not be discoverable via object extraction — the class will have methods for:

- `withElement($name, $value)` will add that element under the given `$name`
  using the provided `$value`. That value MUST be a non-object value. The method
  will return a new instance composing the value. If an element of `$name`
  already existed, this method will replace the value.
- `withElements(array $elements)` expects an associative array of key/value
  pairs to compose in the resource. Like `withElement()`, these will replace
  existing values. The method returns a new instance.
- `embed($name, Resource $resource)` will embed the given `$resource`, under the
  provided `$name`. If another resource already exists under that name, this
  method will create an array with the two values, if they are of the same
  structure (raising an exception if not); if the value is an array already,
  this method will append the new value to that array (if it is of the same
  structure as other elements in the array).

As such, you would be able to directly create resources and manipulate them:

```php
$resource = new Resource();
$resource = $resource->withElements($bookData);
$resource = $resource->withLink(new Link('self', $uriToBook));
$resource = $resource->embed('author', $authorResource);
```

This works when you have scalar data. But we like to work with typed objects,
right? And generate URIs based on our routes?

## Metadata

When we have typed objects, we may want to generate resources by:

- extracting an array of data from the object, likely using zend-hydrator.
- generating a "self" link using a route template; in the case of discrete
  resources, this may also involve using the object identifer to fill a
  placeholder within a route template.
- potentially incorporate some default, non-self links.
- potentially add pagination information in the case of collections.

These are things we tackled in zf-hal previously. That component/module presents
an interesting architecture for mapping resource _metadata_. Resource metadata
describes the various bits of information we need in order to create a complete
resource representation, essentially.

Basic metadata includes the following:

- The PHP class the metadata maps to.
- Any additional links other than a "self" link to include. Essentially, just
  like a `Resource`, the metadata acts as a PSR-13 `EvolvableLinkCollection`.

Metadata for all basic, non-collection resources would also include:

- The extractor to use when extracting the resource for this class.

Metadata for non-generated URL-based resources (i.e., not using the `UrlHelper`)
would include:

- A URL to use for the `self` relation.

Metadata for route-based resources would include:

- The field representing the identifier for the resource, if any.
- The route associated with the resource.
- The placeholder used for the resource identifier in the route, if any.
- Any additional route parameters to include when generating a URI for this
  resource.

Metadata for all collections would include:

- The embedded resource name to use for the collection.

Metadata for non-generated URL-based collections (i.e., not using the
`UrlHelper`) would include:

- A URL to use for the `self` relation.
- Optionally pagination information:
  - does pagination occur as a query string parameter, or via a placeholder (of
    the form `%<page parameter name>%` within the URL string)?
  - what is the name of the query string parameter and/or placeholder?
- Optionally, the query string parameter for indicating a page of results

Metadata for route-based collections would include:

- The route associated with the resource.
- Any additional route parameters to include when generating a URI for this
  resource.
- Optionally pagination information:
  - does pagination occur as a query string parameter, or via a routing parameter?
  - what is the name of the query string parameter and/or routing parameter?
- Optionally, any additional query string parameters to include in a generated URI

Essentially, the metadata subcomponent would have the following hierarchy:

- AbstractMetadata
  - AbstractResourceMetadata
    - UrlBasedResourceMetadata
    - RouteBasedResourceMetadata
  - AbstractCollectionMetadata
    - UrlBasedCollectionMetadata
    - RouteBasedCollectionMetadata

Metadata exists _parallel_ and _orthoganal_ to the actual resources. It is
information that can be used to _generate_ resources themselves.

As such, we'd describe objects in our system that we want to represent using
HAL:

```php
$booksMetadata = new RouteBasedCollectionMetadata(
    BooksCollection::class, // collection class
    'books',                // collection name
    'books'                 // route name
);
$booksMetadata = $booksMetadata->withLink(new Link('search', $urlHelper(
    'books',
    [],
    ['query' => 'search string']
)));

$bookMetadata = new RouteBasedResourceMetadata(
    Book::class,           // resource class
    BookExtractor::class,  // extractor service to use
    'book',                // route associated with resource
    'book_id',             // extracted resource identifier
    'id'                   // route resource identifier
);
```

Metadata is then added to a _map_, which provides methods for determining
if a given class has metadata associated, and, if so, allows retrieval of that
metadata.

```php
$metadataMap = new MetadataMap();
$metadataMap->add($booksMetadata);
$metadataMap->add($bookMetadata);
```

This set of classes _could_ exist independently of any consumer, and could be
provided in the base HAL package. zf-hal could, for instance, be updated to
depend on this package, and it would likely still work exactly as it currently
does.

## Resource Generator

Now that we have links, resources, and metadata, we can look at automating
resource generation from objects.

The resource generator acts as a factory for generating `Resource`
instances. To do its work, it needs:

- A metadata map.
- An extraction plugin manager (`Zend\Hydrator\HydratorPluginManager`).
- The `LinkGenerator`.

```php
$generator = new ResourceGenerator();
$generator->setMetadataMap($container->get(MetadataMap::class));
$generator->setHydratorManager($container->get(HydratorPluginManager::class));
$generator->setLinkGenerator($container->get(LinkGenerator::class));

// OR, more properly:

$generator = new ResourceGenerator(
    $container->get(MetadataMap::class),
    $container->get(HydratorPluginManager::class),
    $container->get(LinkGenerator::class)
);
```

### Using arrays or stdClass objects as resources

You can use plain arrays or `stdClass` instances as resources. When you do, you
will optionally provide the `self` link.

```php
// Without `self` link:
$resource = $generator->fromArray($data);

// With `self` link:
$resource = $generator->fromArray($data, $uri);
```

In such cases, it's likely simpler to directly instantiate and manipulate a
`Resource`.

### Using objects known to the metadata map

```php
// Request is used so that we can pull the route result, if present, and pass it
// to the LinkGenerator; similarly, the request URI instance will also be
// passed to it, allowing generation of an absolute URI if the LinkGenerator
// composes a ServerUrlHelper.
// @var Book $book
// @var ServerRequestInterface $request
$resource = $generator->fromObject($book, $request);
```

### Adding links

As noted earlier, resources act as PSR-13 `EvolvableLinkCollection` instances.

```php
$resource->withLink(new Link('author', (string) $authorUri));
```

## Representations

The base `Resource` class contains a `toArray()` method for generating an array
representation of the HAL resource. Additionally, it implements
`JsonSerializable`, which proxies to the `toArray()` method.

```php
// Finally, we can either cast it to an array:
$arrayRepresentation = $resource->toArray();

// Or it will implement JsonSerializable, allowing this:
$json = json_encode($resource);
```

## Embedded resources

There are two scenarios for embedding resources. The first, and simplest, is
manual, and described earlier:

```php
$author = $generator->fromObject($author, $request);
$resource = $resource->embed('author', $author);
```

The above creates a new HAL resource for the author, and then embeds it in the
original book resource as an `author`.

The second approach is to do so "automagically" from the master resource.

As an example, if any element extracted from the master resource is an object 
known to the metadata map, the generator will call `fromObject()` on that
instance, and then `embed()` the resulting resource in the parent resource,
using the key associated with the object.

If you embed another resource with the same name, this now becomes a
_collection_: an array of resources:

```php
$secondAuthor = $generator->fromObject($secondAuthor, $request);
$resource = $resource->embed('author', $secondAuthor);
```

Internally, the resource will check if the resource to embed matches an existing
key, and, if so, that the structure (data keys) matches those of the existing
items embedded under that key, raising an exception if they do not. If they do,
the embedded resource will be converted to an array containing both elements. If
the embedded resource is already an array, it will validate the resource to
append before appending it.

### Collections

zf-hal differentiated between _entities_ and _collections_, but the HAL
specification makes no such distinction; everything is a _resource_.

A _collection_ is simply a _resource_ containing an embedded resource that is an
array of items of the same type. The generator, on matching a collection to an
`AbstractCollectionMetadata` instance, will do the following:

- If the item IS NOT a zend-paginator Paginator instance:
  - get a count of items if it is countable; otherwise, it will start counting
  - iterate over each item, and:
    - pass the item to the generator in order to generate a resource
    - embed the resource in the parent item, using the resource name for the
      collection from the metadata
    - if a counter is present, increment it
  - Add data to the collection indicating the total count
- If the item IS a zend-paginator Paginator instance:
  - get a count of pages, and add it as data to the resource.
  - retrieve the count indicating the total number of items, and add it as data
    to the resource.
  - if the collection supports pagination:
    - get the current page, using the page retrieved from the request
    - determine if a next, previous, last, or first page are possible based on the
      current page
    - embed links to the discovered pages
    - create the "self" link using the current page, if it is not the first, and
      using either the embedded URL or the composed route
  - if the collection does not support pagination
    - create the "self" link using either the embedded URL or the composed route
  - iterate over the items in the current page:
    - pass the item to the generator in order to generate a resource
    - embed the resource in the parent item, using the resource name for the
      collection from the metadata

All of this happens under the hood, meaning creation of a "collection" resource
is the same as a normal resource:

```php
$books = $generator->fromObject($books, $request);
```

## Rendering / Response generation

JSON rendering is dirt-simple, as we can use the array representation plus
`JsonSerializable` implementation to make it happen. However, that fact does not
set the response content type, and does not address custom content-types or XML.

As such, a response generator/factory will be needed. It will accept the
following:

- A request instance, in order to negotiate which format to generate.
- The resource to create a representation for.
- Optionally, the specific content-type, minus representation format, to return
  in the generated response.

```php
use Hal\ResponseFactory;
use Zend\Diactoros\Response;

$factory = new HalResponseFactory(new Response());
$response = $factory->createResponse($request, $resource, 'application/vnd.book');
```

You can force a representation by providing a request with an alternate `Accept`
header:

```php
// Force a JSON representation:
$response = $factory->createResponse(
    $request->withHeader('Accept', 'application/json'),
    $resource,
    'application/vnd.book'
);
```

The response generator will determine which representation format to use,
defaulting to XML, and then serialize accordingly.
