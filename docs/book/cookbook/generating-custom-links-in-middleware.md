# Generating custom links in middleware and request handlers

In most cases, you can rely on the `ResourceGenerator` to generate self
relational links, and, in the case of paginated collections, pagination links.

What if you want to generate other links to include in your resources, though?

The `ResourceGenerator` provides access to the metadata map, hydrators, and link
generator via getter methods:

- `getMetadataMap()`
- `getHydrators()`
- `getLinkGenerator()`

We can thus use these in order to generate custom links as needed.

## Creating a custom link to include in a resource

In our first scenario, we'll create a "search" link for a resource.

We'll assume that you have composed a `ResourceGenerator` instance in your
middleware, and assigned it to the `$resourceGenerator` property.

The link we want to generate will look something like
`/api/books?query={searchParms}`, and map to a route named `books`.

```php
$searchLink = $this->resourceGenerator
    ->getLinkGenerator()
    ->templatedFromRoute(
        'search',
        $request,
        'books',
        [],
        ['query' => '{searchTerms}']
    );
```

You could then compose it in your resource:

```php
$resource = $resource->withLink($searchLink);
```

## Adding metadata for generated links

In our second scenario, we'll consider a collection endpoint. It might include a
`per_page` query string argument, to allow defining how many results to return
per page, a `sort` argument, and a `query` argument indicating the search
string. We know these at _runtime_, but not at the time we create our
configuration, so we need to inject them _after_ we have our metadata created,
but _before_ we generate our resource, so that the pagination links are
correctly generated.

```php
$queryParams = $request->getQueryParams();
$query       = $queryParams['query'] ?? '';
$perPage     = $queryParams['per_page'] ?? 25;
$sort        = $queryParams['sort'] ?? '';
$metadataMap = $this->resourceGenerator->getMetadataMap();
$metadata    = $metadataMap->get(BookCollection::class);

$metadataQuery = $origMetadataQuery = $metadata->getQueryStringArguments();

if ('' !== $query) {
    $metadataQuery = array_merge($metadataQuery, ['query' => $query]);
}

if ('' !== $perPage) {
    $metadataQuery = array_merge($metadataQuery, ['per_page' => $perPage]);
}

if ('' !== $sort) {
    $metadataQuery = array_merge($metadataQuery, ['sort' => $sort]);
}

$metadata->setQueryStringArguments($metadataQuery);

// ...

$resource = $this->resourceGenerator->fromObject($books, $request);

// Reset query string arguments
$metadata->setQueryStringArguments($origMetadataQuery);
```

This will lead to links with URIs such as
`/api/books?query=Adams&per_page=5&sort=DESC&page=4`.
