# Hypertext Application Language

[Hypertext Application Language](http://stateless.co/hal_specification.html), or
HAL) is a [proposed IETF specification](https://tools.ietf.org/html/draft-kelly-json-hal-08)
for representing API resources and their relations with hyperlinks. While the
original specification targets JSON, an additional IETF proposal
[targets XML](https://tools.ietf.org/html/draft-michaud-xml-hal-01).

HAL is a minimal specification, and addresses three specific things:

- How to represent the elements of an API resource.
- How to represent hypertext links of an API resource.
- How to represent child resources.

## Resources

HAL opts to keep out of the way. Where other specifications may push the data
for a resource into a subkey (e.g., "data", "collection.items", etc.), HAL
specifies resources as the primary payload.

As such, a resource is simply a _document_:

```json
{
  "id": "XXXX-YYYY-ZZZZ-AAAA",
  "title": "Life, the Universe, and Everything",
  "author": "Adams, Douglas"
}
```

For XML documents, the element `<resource>` is reserved to detail the resource;
every other element represents the document:

```xml
<resource>
  <id>XXXX-YYYY-ZZZZ-AAAA</id>
  <title>Life, the Universe, and Everything</title>
  <author>Adams, Douglas</author>
</resource>
```

This decision makes both consuming and generating HAL payloads trivial.

## Links

One goal of REST is to allow any given resource to provide _hypertext controls_,
or _links_, allowing the consumer to know what they can do next. Most resources
will provide a _self relational link_, so that the consumer knows how to request
the resource again. However, a consumer might want to know what other actions
are possible. For example, they may want to know:

- how to get a list of related resources
- how to access the first, previous, next, or last pages of a collection of
  resources
- what resources are related: e.g., transactions, products, invoices, users,
  etc.

HAL addresses _how_ to provide such links. This is necessary because JSON has no
specific semantics around linking, and XML, while it has _some_ semantics, does
not cover how to provide _multiple_ links for a given element.

HAL addresses JSON by reserving a special `_links` property, and specifying a
structure for how links are represented. Each element of the `_links` property
is named for the link relation, and the value is either an array, or a link
object. A link object contains minimally an `href` property, with several other
properties allowed. As an example:

```json
{
  "_links": {
    "self": { "href": "/api/books/XXXX-YYYY-ZZZZ-AAAA" },
    "books": { "href": "/api/books" }
  }
}
```

At this point, the consumer knows they can access the current resource via the
"self" relation, and a collection of "books" via the URI "/api/books".

HAL addresses links in XML with two semantics. First, the `<resource>` element
can contain linking information, using the "rel" and "href" attributes (and any
others necessary to describe the link).  Typically, the "self" relational link
is defined in the `<resource>` element. Second, the specification also reserves
the `<link>` element; the relation, URI, and other attributes become attributes
of that XML element.

An equivalent XML document to the JSON document above looks like the following:

```xml
<resource rel="self" href="/api/books/XXXX-YYYY-ZZZZ-AAAA">
  <link rel="books" href="/api/books"/>
</resource>
```

## Child Resources

An API payload may have _child resources_ for several reasons:

- The resource may be related to the current payload, and providing it directly
  would prevent another request to the API.
- The payload may represent a _collection_ of resources (or even _multiple_
  collections of resources).

Generally, a child resource represents a _relation_. As such, HAL has very
specific semantics for providing them.

In JSON documents, the specification reserves the property `_embedded`. This is
an object, with the keys being the _relations_, and the values either resources,
or arrays of resources. Each resource follows the same structure as a basic
HAL resource, with a `_links` member, other members representing the resource,
and optionally an `_embedded` member.

```json
{
    "_links": {
        "self": { "href": "/api/books?page=7" },
        "first": { "href": "/api/books?page=1" },
        "prev": { "href": "/api/books?page=6" },
        "next": { "href": "/api/books?page=8" },
        "last": { "href": "/api/books?page=17" }
        "search": {
            "href": "/api/books?query={searchTerms}",
            "templated": true
        }
    },
    "_embedded": {
        "book": [
            {
                "_links": {
                    "self": { "href": "/api/books/1234" }
                }
                "id": 1234,
                "title": "Hitchhiker's Guide to the Galaxy",
                "author": "Adams, Douglas"
            },
            {
                "_links": {
                    "self": { "href": "/api/books/6789" }
                }
                "id": 6789,
                "title": "Ancillary Justice",
                "author": "Leckie, Ann"
            }
        ]
    },
    "_page": 7,
    "_per_page": 2,
    "_total": 33
}
```

The above represents a _collection_ of _book_ resources.

To address XML, the specification uses the `<resource>` element to embed
additional resources. Resources of the same type use the same `rel` attribute.
The XML equivalent of the above JSON documentation thus becomes:

```xml
<resource rel="self" href="/api/books?page=7">
    <link rel="first" href="/api/books?page=1"/>
    <link rel="prev" href="/api/books?page=6"/>
    <link rel="next" href="/api/books?page=8"/>
    <link rel="last" href="/api/books?page=17" templated="true"/>
    <resource rel="book" href="/api/books/1234">
        <id>1234</id>
        <title>Hitchhiker's Guide to the Galaxy</title>
        <author>Adams, Douglas</author>
    </resource>
    <resource rel="book" href="/api/books/6789">
        <id>6789</id>
        <title>Ancillary Justice</title>
        <author>Leckie, Ann</author>
    </resource>
    <_page>7</_page>
    <_per_page>2</_per_page>
    <_total>33</_total>
</resource>
```

## Summary

With these three semantics &mdash; resources, links, and child resources &mdash;
HAL allows you to describe any payload, and provide the hypertext controls
necessary to allow API consumers to know what resources they can access next.

The next step, then, is learning how to create HAL payloads for your API!
