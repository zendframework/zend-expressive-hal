# Generating HAL from Doctrine

> - Since 1.3.0

[Doctrine](https://www.doctrine-project.org/) is a well-known and popular Object
Relational Mapper; you will find it in use across pretty much every PHP
framework. Expressive is no different.

How do you generate HAL for Doctrine resources? As it turns out, the same way
you would for any other objects you might have: create metadata mapping the
objects you want to represent to the routes and the hydrators/collections to use
when extracting them.

## Example: Paginated Albums

In this example, we have an entity named `Album` that we want to expose via a
paginated HAL representation. Over the course of the example, we will create a
custom collection class based off of the Doctrine `Paginator` class, and map it
as a HAL collection.

Our first step is defining an entity:

```php
declare(strict_types=1);

namespace Album\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/basic-mapping.html
 *
 * @ORM\Entity
 * @ORM\Table(name="albums")
 **/
class Album
{
    /**
     * @var Uuid
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $title;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $modified;

    /**
     * @return Uuid
     */
    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     * @throws \Exception
     */
    public function setCreated(\DateTime $created = null): void
    {
        if (!$created && empty($this->getId())) {
            $this->created = new \DateTime("now");
        } else {
            $this->created = $created;
        }
    }

    /**
     * @return \DateTime
     */
    public function getModified(): \DateTime
    {
        return $this->modified;
    }

    /**
     * @param \DateTime $modified
     * @throws \Exception
     */
    public function setModified(\DateTime $modified = null): void
    {
        if (!$modified) {
            $this->modified = new \DateTime("now");
        } else {
            $this->modified = $modified;
        }
    }
}
```

In order to work with this, we need to provide Doctrine persistence mapping
configuration. We will do this in the `ConfigProvider` for this module:

```php
declare(strict_types=1);

namespace Album;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'doctrine'     => $this->getDoctrineEntities(),
        ];
    }

    public function getDependencies() : array
    {
        return [
        ];
    }

    public function getDoctrineEntities() : array
    {
        return [
            'driver' => [
                'orm_default' => [
                    'class' => MappingDriverChain::class,
                    'drivers' => [
                        'Album\Entity' => 'album_entity',
                    ],
                ],
                'album_entity' => [
                    'class' => AnnotationDriver::class,
                    'cache' => 'array',
                    'paths' => [__DIR__ . '/Entity'],
                ],
            ],
        ];
    }
}
```

Next, in order to provide a HAL _collection_ representation, we will create a
custom `Doctrine\ORM\Tools\Pagination\Paginator` extension:

```php
declare(strict_types=1);

namespace Album\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;

class AlbumCollection extends Paginator
{
}
```

From here, we will add configuration of our HAL metadata map to the
`ConfigProvider`. First, we will add the following method to configure both our
entity and our collection:

```php
// Add these imports to the top of the class file
use Zend\Expressive\Hal\Metadata\RouteBasedCollectionMetadata;
use Zend\Expressive\Hal\Metadata\RouteBasedResourceMetadata;
use Zend\Hydrator\ReflectionHydrator;


    // Add this method inside the ConfigProvider class:
    public function getHalMetadataMap()
    {
        return [
            [
                '__class__'      => RouteBasedResourceMetadata::class,
                'resource_class' => Entity\Album::class,
                'route'          => 'albums.show', // assumes a route named 'albums.show' has been created
                'extractor'      => ReflectionHydrator::class,
            ],
            [
                '__class__'           => RouteBasedCollectionMetadata::class,
                'collection_class'    => Entity\AlbumCollection::class,
                'collection_relation' => 'album',
                'route'               => 'albums.list', // assumes a route named 'albums.list' has been created
            ],
        ];
    }
```

Then, within the `__invoke()` method, we will assign the return value of that
method to the key `MetadataMap::class`:

```php
// Add this import to the top of the class file:
use Zend\Expressive\Hal\Metadata\MetadataMap;

    // Modify this ConfigProvider method to read:
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            'doctrine'     => $this->getDoctrineEntities(),
            MetadataMap::class => $this->getHalMetadataMap(),
        ];
    }
```

With these in place, we can write a handler that will display a collection as
follows:

```php
declare(strict_types=1);

namespace Album\Handler;

use Album\Entity\Album;
use Album\Entity\AlbumCollection;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Hal\HalResponseFactory;
use Zend\Expressive\Hal\ResourceGenerator;

class ListAlbumsHandler implements RequestHandlerInterface
{
    protected $entityManager;
    protected $pageCount;
    protected $responseFactory;
    protected $resourceGenerator;

    public function __construct(
        EntityManager $entityManager,
        int $pageCount,
        HalResponseFactory $responseFactory,
        ResourceGenerator $resourceGenerator
    ) {
        $this->entityManager     = $entityManager;
        $this->pageCount         = $pageCount;
        $this->responseFactory   = $responseFactory;
        $this->resourceGenerator = $resourceGenerator;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $repository = $this->entityManager->getRepository(Album::class);

        $query = $repository
            ->createQueryBuilder('c')
            ->getQuery();
        $query->setMaxResults($this->pageCount);

        $paginator = new AlbumCollection($query);
        $resource  = $this->resourceGenerator->fromObject($paginator, $request);
        return $this->responseFactory->createResponse($request, $resource);
    }
}
```

And another handler for displaying an individual album:

```php
declare(strict_types=1);

namespace Album\Handler;

use Album\Entity\Album;
use Doctrine\ORM\EntityManager;
use Zend\Expressive\Helper\ServerUrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowAlbumHandler implements RequestHandlerInterface
{
    protected $entityManager;
    protected $urlHelper;

    /**
     * AnnouncementsViewHandler constructor.
     * @param EntityManager $entityManager
     * @param ServerUrlHelper $urlHelper
     */
    public function __construct(
        EntityManager $entityManager,
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $entityRepository = $this->entityManager->getRepository(Album::class);

        $result = $entityRepository->find($request->getAttribute('id'));

        if (empty($return)) {
            throw new RuntimeException('Not Found', 404);
        }

        $resource = $this->resourceGenerator->fromObject($result, $request);
        return $this->responseFactory->createResponse($request, $resource);
    }
}
```

In the above example, we map our `Album` entity such that:

- it is route-based; we will generate relational links to such entities based on
  existing routing definitions. (In this example, "albums.show".)
- it uses the `ReflectionHydrator` from the [zend-hydrator
  package](https://docs.zendframework.com/zend-hydrator) to extract a
  representation of the object to use with HAL.

For our `AlbumCollection`, we define it such that:

- it, too, is route-based. (In this example, it maps to the route
  "albums.list".)
- the collection will map to the property "album".

Since these mappings are in place, our handlers need only use the Doctrine
`EntityManager` in order to retrieve the appropriate repository, and from there
either retrieve appropriate entities (in the case of the `ShowAlbumHandler`), or
seed a collection paginator (in the case of the `ListAlbumsHandler`). These
values are known by the metadata map, and, as such, we can generate HAL
resources for them without needing any other information.

## Example: Doctrine Collections

Sometimes we will want to return an entire collection at once. The `getResult()`
method of `Doctrine\ORM\Query` will return an array of results by default, with
each item in the array an object based on provided mappings.

zend-expressive-hal will not work with arrays by default, as it needs a typed
object in order to appropriately map it to a representation. To accomplish this,
then, we have several options:

- Create a custom extension of an SPL iterator such as `ArrayIterator` to wrap
  the results.
- Create a custom extension of something like `Doctrine\Common\Collections\ArrayCollection`
  to wrap the results.

The following examples are based on the paginated collection from above;
familiarize yourself with that code before continuing.

The first change we will make is to modify our `AlbumCollection` to extend the
Doctrine `ArrayCollection`, instead of its `Paginator`:

```php
namespace Album\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class AlbumCollection extends ArrayCollection
{
}
```

The only other changes we then need to make are to our `ListAlbumsHandler`:

```php
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $repository = $this->entityManager->getRepository(Album::class);

        // Note that this removes the call to setMaxResults()
        $query = $repository
            ->createQueryBuilder('c')
            ->getQuery();

        // Note that we pass the collection class the query result, and not the
        // query instance:
        $collection = new AlbumCollection($query->getResult());

        $resource  = $this->resourceGenerator->fromObject($collection, $request);
        return $this->responseFactory->createResponse($request, $resource);
    }
```

With these in place, we will now get representation of all items returned by the
query.
