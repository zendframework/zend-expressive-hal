# TODO

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
- [ ] ResourceGenerator class
  - [ ] Tests
    - [x] URL-based resources
    - [x] Route-based resources
    - [x] URL-based collections
    - [x] Route-based collections
    - [ ] What happens to object properties of instances?
  - [x] Implementation
- [x] Representation/response generation
  - [x] XML representations
  - [x] Content-negotiation-based response generator
- [ ] Considerations based on implementation:
  - [ ] Rename `Resource` class to something else (as resource has a soft
    reservation as a keyword starting in PHP 7)
  - [ ] Should RouteBasedCollectionMetadata allow specifying route parameters?
    May be useful/required to allow creating sub-resources.
  - [ ] Refactor ResourceGeneratorTest to use custom assertions (`getLinkByRel()`,
    `assertLink()`).
