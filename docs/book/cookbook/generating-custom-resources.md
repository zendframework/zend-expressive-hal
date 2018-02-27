# Generating custom resources

The `ResourceGenerator` allows composing `Zend\Expressive\Hal\ResourceGenerator\StrategyInterface`
instances. The `StrategyInterface` defines the following:

```php
namespace Zend\Expressive\Hal\ResourceGenerator;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Metadata;
use Zend\Expressive\Hal\ResourceGenerator;

interface StrategyInterface
{
    /**
     * @param object $instance Instance from which to create Resource.
     * @throws Exception\UnexpectedMetadataTypeException for metadata types the
     *     strategy cannot handle.
     */
    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource;
}
```

When you register a strategy, you will map a metadata type to the strategy; the
`ResourceGenerator` will then call your strategy whenever it encounteres
metadata of that type.

```php
$resourceGenerator->addStrategy(CustomMetadata::class, CustomStrategy::class);

// or:
$resourceGenerator->addStrategy(CustomMetadata::class, $strategyInstance);
```

You can also add your strategies via the configuration:

```php
return [
    'zend-expressive-hal' => [
        'resource-generator' => [
            'strategies' => [
                CustomMetadata::class => CustomStrategy::class,
            ],
        ],
    ],
];
```

If a strategy already is mapped for the given metadata type, this method will
override it.

To facilitate common operations, this library provides two traits,
`Zend\Expressive\Hal\ResourceGenerator\ExtractCollectionTrait` and
`Zend\Expressive\Hal\ResourceGenerator\ExtractInstanceTrait`; inspect these if you
decide to write your own strategies.

In order for the `MetadataMap` to be able to use your `CustomMetadata` you need to register 
a factory (implementing `Zend\Expressive\Hal\Metadata\MetadataFactoryInterface`) for it.
You can register them via the configuration:

```php
return [
    'zend-expressive-hal' => [
        'metadata-factories' => [
            CustomMetadata::class => CustomMetadataFactory::class,
        ],
    ],
];
```
