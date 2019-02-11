<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator;

use Countable;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;
use Zend\Expressive\Hal\Metadata\AbstractCollectionMetadata;
use Zend\Expressive\Hal\ResourceGenerator;
use Zend\Paginator\Paginator;

use function get_class;
use function in_array;
use function sprintf;

trait ExtractCollectionTrait
{
    private $paginationTypes = [
        AbstractCollectionMetadata::TYPE_PLACEHOLDER,
        AbstractCollectionMetadata::TYPE_QUERY,
    ];

    abstract protected function generateLinkForPage(
        string $rel,
        int $page,
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : Link;

    abstract protected function generateSelfLink(
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : Link;

    private function extractCollection(
        Traversable $collection,
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        if (! $metadata instanceof AbstractCollectionMetadata) {
            throw Exception\UnexpectedMetadataTypeException::forCollection($metadata, get_class($this));
        }

        if ($collection instanceof Paginator) {
            return $this->extractPaginator($collection, $metadata, $resourceGenerator, $request);
        }

        if ($collection instanceof DoctrinePaginator) {
            return $this->extractDoctrinePaginator($collection, $metadata, $resourceGenerator, $request);
        }

        return $this->extractIterator($collection, $metadata, $resourceGenerator, $request);
    }

    /**
     * Generates a paginated hal resource from a collection
     *
     * @param Paginator $collection
     * @param AbstractCollectionMetadata $metadata
     * @param ResourceGenerator $resourceGenerator
     * @param ServerRequestInterface $request
     * @return HalResource
     * @throws Exception\OutOfBoundsException if requested page if outside the available pages
     */
    private function extractPaginator(
        Paginator $collection,
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        $data      = ['_total_items' => $collection->getTotalItemCount()];
        $pageCount = $collection->count();

        return $this->createPaginatedCollectionResource(
            $pageCount,
            $data,
            function (int $page) use ($collection) {
                $collection->setCurrentPageNumber($page);
            },
            $collection,
            $metadata,
            $resourceGenerator,
            $request
        );
    }

    /**
     * Extract a collection from a Doctrine paginator.
     *
     * When pagination is requested, and a valid page is found, calls the
     * paginator's `setFirstResult()` method with an offset based on the
     * max results value set on the paginator.
     */
    private function extractDoctrinePaginator(
        DoctrinePaginator $collection,
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        $query      = $collection->getQuery();
        $totalItems = count($collection);
        $perPage    = $query->getMaxResults();
        $pageCount  = (int) ceil($totalItems / $perPage);

        $data  = ['_total_items' => $totalItems];

        return $this->createPaginatedCollectionResource(
            $pageCount,
            $data,
            function (int $page) use ($query, $perPage) {
                $query->setFirstResult($perPage * ($page - 1));
            },
            $collection,
            $metadata,
            $resourceGenerator,
            $request
        );
    }

    private function extractIterator(
        Traversable $collection,
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        $isCountable = $collection instanceof Countable;
        $count = $isCountable ? $collection->count() : 0;

        $resources = [];
        foreach ($collection as $item) {
            $resources[] = $resourceGenerator->fromObject($item, $request);
            $count = $isCountable ? $count : $count + 1;
        }

        $data = ['_total_items' => $count];
        $links = [$this->generateSelfLink(
            $metadata,
            $resourceGenerator,
            $request
        )];

        return new HalResource($data, $links, [
            $metadata->getCollectionRelation() => $resources,
        ]);
    }

    /**
     * Create a collection resource representing a paginated set.
     *
     * Determines if the metadata uses a query or placeholder pagination type.
     * If not, it generates a self relational link, and then immediately creates
     * and returns a collection resource containing every item in the collection.
     *
     * If it does, it pulls the pagination parameter from the request using the
     * appropriate source (query string arguments or routing parameter), and
     * then checks to see if we have a valid page number, throwing an out of
     * bounds exception if we do not. From the page, it then determines which
     * relational pagination links to create, including a `self` relation,
     * and aggregates the current page and total page count in the $data array
     * before calling on createCollectionResource() to generate the final
     * HAL resource instance.
     *
     * @param array<string, mixed> $data Data to render in the root of the HAL
     *     resource.
     * @param callable $notifyCollectionOfPage A callback that receives an integer
     *     $page argument; this should be used to update the paginator instance
     *     with the current page number.
     */
    private function createPaginatedCollectionResource(
        int $pageCount,
        array $data,
        callable $notifyCollectionOfPage,
        iterable $collection,
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        $links               = [];
        $paginationParamType = $metadata->getPaginationParamType();

        if (! in_array($paginationParamType, $this->paginationTypes, true)) {
            $links[] = $this->generateSelfLink($metadata, $resourceGenerator, $request);
            return $this->createCollectionResource(
                $links,
                $data,
                $collection,
                $metadata,
                $resourceGenerator,
                $request
            );
        }

        $paginationParam = $metadata->getPaginationParam();
        $page = $paginationParamType === AbstractCollectionMetadata::TYPE_QUERY
            ? (int) ($request->getQueryParams()[$paginationParam] ?? 1)
            : (int) $request->getAttribute($paginationParam, 1);

        if ($page < 1 || ($page > $pageCount && $pageCount > 0)) {
            throw new Exception\OutOfBoundsException(sprintf(
                'Page %d is out of bounds. Collection has %d page%s.',
                $page,
                $pageCount,
                $pageCount > 1 ? 's' : ''
            ));
        }

        $notifyCollectionOfPage($page);

        $links[] = $this->generateLinkForPage('self', $page, $metadata, $resourceGenerator, $request);
        if ($page > 1) {
            $links[] = $this->generateLinkForPage('first', 1, $metadata, $resourceGenerator, $request);
            $links[] = $this->generateLinkForPage('prev', $page - 1, $metadata, $resourceGenerator, $request);
        }
        if ($page < $pageCount) {
            $links[] = $this->generateLinkForPage('next', $page + 1, $metadata, $resourceGenerator, $request);
            $links[] = $this->generateLinkForPage('last', $pageCount, $metadata, $resourceGenerator, $request);
        }

        $data['_page'] = $page;
        $data['_page_count'] = $pageCount;

        return $this->createCollectionResource(
            $links,
            $data,
            $collection,
            $metadata,
            $resourceGenerator,
            $request
        );
    }

    /**
     * Create the collection resource with its embedded resources.
     *
     * Iterates the collection, passing each item to the resource generator
     * to produce a HAL resource. These are then used to create an embedded
     * relation in a master HAL resource that contains metadata around the
     * collection itself (number of items, number of pages, etc.), and any
     * relational links.
     */
    private function createCollectionResource(
        array $links,
        array $data,
        iterable $collection,
        AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        $resources = [];
        foreach ($collection as $item) {
            $resources[] = $resourceGenerator->fromObject($item, $request);
        }

        return new HalResource($data, $links, [
            $metadata->getCollectionRelation() => $resources,
        ]);
    }
}
