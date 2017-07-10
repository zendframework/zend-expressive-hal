# TODO

- [x] Development
  - [x] Link class
    - [x] Link serialization to array
    - [x] Link serialization to JSON via `JsonSerializable`
  - [x] LinkCollection trait
  - [x] Resource class
  - [x] UrlGenerator interface
  - [x] LinkGenerator class
  - [x] Expressive-based UrlGenerator (and related factory)
  - [x] Metadata
    - [x] Basic resource metadata (PHP class, link collection)
    - [x] URL-based resources
    - [x] Route-based resources
    - [x] Basic collection metadata (embedded resource name + resource metadata)
    - [x] URL-based collections
    - [x] Route-based collections
  - [x] ResourceGenerator class
    - [x] Tests
      - [x] URL-based resources
      - [x] Route-based resources
      - [x] URL-based collections
      - [x] Route-based collections
      - [x] What happens to object properties of instances?
    - [x] Implementation
  - [x] Representation/response generation
    - [x] XML representations
    - [x] Content-negotiation-based response generator
  - [x] Considerations based on implementation:
    - [x] Rename `Resource` class to something else (as resource has a soft
      reservation as a keyword starting in PHP 7)
    - [ ] ~~Should UrlBasedResourceMetadata and related Strategy allow usage of
      placeholders within the URL for the identifier? This could be done in a
      fashion similar to pagination.~~ No, it should not. If you need
      placeholders, use routes.
    - [x] Should RouteBasedCollectionMetadata allow specifying route parameters?
      May be useful/required to allow creating sub-resources.
    - [x] Refactor ResourceGeneratorTest to use custom assertions (`getLinkByRel()`,
      `assertLink()`).
  - [x] Configuration-driven MetadataMap
- [ ] Documentation
  - Resources
  - Links
    - UrlGenerator
  - Representations
  - Automating resource and collection generation from typed objects
    - Hydrators
    - Metadata
    - ResourceGenerator
    - Creating your own metadata classes
    - Creating your own resource generator strategies
