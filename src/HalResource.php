<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use InvalidArgumentException;
use JsonSerializable;
use Psr\Link\EvolvableLinkProviderInterface;
use Psr\Link\LinkInterface;
use RuntimeException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_push;
use function array_reduce;
use function array_shift;
use function array_walk;
use function count;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_object;
use function sort;
use function sprintf;

/**
 * Object representation of Hypertext Application Language resource.
 *
 * The class name "HalResource" was chosen as "resource" has been given a
 * soft designation as a future keyword in PHP as of PHP 7; choosing this
 * name now makes the class future-proof.
 */
class HalResource implements EvolvableLinkProviderInterface, JsonSerializable
{
    use LinkCollection;

    /**
     * @var array All data to represent.
     */
    private $data = [];

    /**
     * @var HalResource[]
     */
    private $embedded = [];

    /**
     * @param array $data
     * @param LinkInterface[] $links
     * @param HalResource[][] $embedded
     */
    public function __construct(array $data = [], array $links = [], array $embedded = [])
    {
        $context = __CLASS__;
        array_walk($data, function ($value, $name) use ($context) {
            $this->validateElementName($name, $context);
            if (! empty($value)
                && ($value instanceof self || $this->isResourceCollection($value, $name, $context))
            ) {
                $this->embedded[$name] = $value;
                return;
            }
            $this->data[$name] = $value;
        });

        array_walk($embedded, function ($resource, $name) use ($context) {
            $this->validateElementName($name, $context);
            $this->detectCollisionWithData($name, $context);
            if (! ($resource instanceof self || $this->isResourceCollection($resource, $name, $context))) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid embedded resource provided to %s constructor with name "%s"',
                    $context,
                    $name
                ));
            }
            $this->embedded[$name] = $resource;
        });

        if (array_reduce($links, function ($containsNonLinkItem, $link) {
            return $containsNonLinkItem || ! $link instanceof LinkInterface;
        }, false)) {
            throw new InvalidArgumentException('Non-Link item provided in $links array');
        }
        $this->links = $links;
    }

    /**
     * Retrieve a named element from the resource.
     *
     * If the element does not exist, but a corresponding embedded resource
     * is present, the embedded resource will be returned.
     *
     * If the element does not exist at all, a null value is returned.
     *
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException if $name is empty
     * @throws InvalidArgumentException if $name is a reserved keyword
     */
    public function getElement(string $name)
    {
        $this->validateElementName($name, __METHOD__);

        if (! isset($this->data[$name]) && ! isset($this->embedded[$name])) {
            return null;
        }

        if (isset($this->embedded[$name])) {
            return $this->embedded[$name];
        }

        return $this->data[$name];
    }

    /**
     * Retrieve all elements of the resource.
     *
     * Returned as a set of key/value pairs. Embedded resources are mixed
     * in as `HalResource` instances under the associated key.
     */
    public function getElements() : array
    {
        return array_merge($this->data, $this->embedded);
    }

    /**
     * Return an instance including the named element.
     *
     * If the value is another resource, proxies to embed().
     *
     * If the $name existed in the original instance, it will be overwritten
     * by $value in the returned instance.
     *
     * @param string $name
     * @param mixed $value
     * @return HalResource
     * @throws InvalidArgumentException if $name is empty
     * @throws InvalidArgumentException if $name is a reserved keyword
     * @throws RuntimeException if $name is already in use for an embedded
     *     resource.
     */
    public function withElement(string $name, $value) : HalResource
    {
        $this->validateElementName($name, __METHOD__);

        if (! empty($value)
            && ($value instanceof self || $this->isResourceCollection($value, $name, __METHOD__))
        ) {
            return $this->embed($name, $value);
        }

        $this->detectCollisionWithEmbeddedResource($name, __METHOD__);

        $new = clone $this;
        $new->data[$name] = $value;
        return $new;
    }

    /**
     * Return an instance removing the named element or embedded resource.
     *
     * @param string $name
     * @return HalResource
     * @throws InvalidArgumentException if $name is empty
     * @throws InvalidArgumentException if $name is a reserved keyword
     */
    public function withoutElement(string $name) : HalResource
    {
        $this->validateElementName($name, __METHOD__);

        if (isset($this->data[$name])) {
            $new = clone $this;
            unset($new->data[$name]);
            return $new;
        }

        if (isset($this->embedded[$name])) {
            $new = clone $this;
            unset($new->embedded[$name]);
            return $new;
        }

        return $this;
    }

    /**
     * Return an instance containing the provided elements.
     *
     * If any given element exists, either as top-level data or as an embedded
     * resource, it will be replaced. Otherwise, the new elements are added to
     * the resource returned.
     */
    public function withElements(array $elements) : HalResource
    {
        $resource = $this;
        foreach ($elements as $name => $value) {
            $resource = $resource->withElement($name, $value);
        }

        return $resource;
    }

    /**
     * @param string $name
     * @param HalResource|HalResource[] $resource
     * @param bool $forceCollection Whether or not a single resource or an
     *     array containing a single resource should be represented as an array of
     *     resources during representation.
     * @return HalResource
     */
    public function embed(string $name, $resource, bool $forceCollection = false) : HalResource
    {
        $this->validateElementName($name, __METHOD__);
        $this->detectCollisionWithData($name, __METHOD__);
        if (! $resource instanceof self && ! $this->isResourceCollection($resource, $name, __METHOD__)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a %s instance or array of %s instances; received %s',
                __METHOD__,
                __CLASS__,
                __CLASS__,
                is_object($resource) ? get_class($resource) : gettype($resource)
            ));
        }
        $new = clone $this;
        $new->embedded[$name] = $this->aggregateEmbeddedResource($name, $resource, __METHOD__, $forceCollection);
        return $new;
    }

    public function toArray() : array
    {
        $resource = $this->data;

        $links = $this->serializeLinks();
        if (! empty($links)) {
            $resource['_links'] = $links;
        }

        $embedded = $this->serializeEmbeddedResources();
        if (! empty($embedded)) {
            $resource['_embedded'] = $embedded;
        }

        return $resource;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @throws InvalidArgumentException if $name is empty
     * @throws InvalidArgumentException if $name is a reserved keyword
     */
    private function validateElementName(string $name, string $context) : void
    {
        if (empty($name)) {
            throw new InvalidArgumentException(sprintf(
                '$name provided to %s cannot be empty',
                $context
            ));
        }
        if (in_array($name, ['_links', '_embedded'], true)) {
            throw new InvalidArgumentException(sprintf(
                'Error calling %s: %s is not a reserved element $name and cannot be retrieved',
                $context,
                $name
            ));
        }
    }

    private function detectCollisionWithData(string $name, string $context) : void
    {
        if (isset($this->data[$name])) {
            throw new RuntimeException(sprintf(
                'Collision detected in %s; attempt to embed resource matching element name "%s"',
                $context,
                $name
            ));
        }
    }

    private function detectCollisionWithEmbeddedResource(string $name, string $context) : void
    {
        if (isset($this->embedded[$name])) {
            throw new RuntimeException(sprintf(
                'Collision detected in %s; attempt to add element matching resource name "%s"',
                $context,
                $name
            ));
        }
    }

    /**
     * Determine how to aggregate an embedded resource.
     *
     * If no embedded resource exists with the given name, returns it verbatim.
     *
     * If another does, it compares the new resource with the old, raising an
     * exception if they differ in structure, and returning an array containing
     * both if they do not.
     *
     * If another does as an array, it compares the new resource with the
     * structure of the first element; if they are comparable, then it appends
     * the new one to the list.
     *
     * @return HalResource|HalResource[]
     */
    private function aggregateEmbeddedResource(string $name, $resource, string $context, bool $forceCollection)
    {
        if (! isset($this->embedded[$name])) {
            return $forceCollection ? [$resource] : $resource;
        }

        // $resource is an collection; existing individual or collection resource exists
        if (is_array($resource)) {
            return $this->aggregateEmbeddedCollection($name, $resource, $context);
        }

        // $resource is a HalResource; existing resource is also a HalResource
        if ($this->embedded[$name] instanceof self) {
            $this->compareResources(
                $this->embedded[$name],
                $resource,
                $name,
                $context
            );
            return [$this->embedded[$name], $resource];
        }

        // $resource is a HalResource; existing collection present
        $this->compareResources(
            $this->firstResource($this->embedded[$name]),
            $resource,
            $name,
            $context
        );
        $collection = $this->embedded[$name];
        array_push($collection, $resource);
        return $collection;
    }

    private function aggregateEmbeddedCollection(string $name, array $collection, string $context) : array
    {
        $original = $this->embedded[$name] instanceof self ? [$this->embedded[$name]] : $this->embedded[$name];
        $this->compareResources(
            $this->firstResource($original),
            $this->firstResource($collection),
            $name,
            $context
        );
        return $original + $collection;
    }

    /**
     * Return the first resource in a list.
     *
     * Exists as array_shift is destructive, and we cannot necessarily know the
     * index of the first element.
     */
    private function firstResource(array $resources)
    {
        foreach ($resources as $resource) {
            return $resource;
        }
    }

    private function isResourceCollection($value, string $name, string $context) : bool
    {
        if (! is_array($value)) {
            return false;
        }

        if (! array_reduce($value, function ($isResource, $item) {
            return $isResource && $item instanceof self;
        }, true)) {
            return false;
        }

        $resource = $this->firstResource($value);
        return array_reduce($value, function ($matchesCollection, $item) use ($name, $resource, $context) {
            return $matchesCollection && $this->compareResources($resource, $item, $name, $context);
        }, true);
    }

    private function serializeLinks()
    {
        $relations = array_reduce($this->links, function (array $byRelation, LinkInterface $link) {
            $representation = array_merge($link->getAttributes(), [
                'href' => $link->getHref(),
            ]);
            if ($link->isTemplated()) {
                $representation['templated'] = true;
            }

            $linkRels = $link->getRels();
            array_walk($linkRels, function ($rel) use (&$byRelation, $representation) {
                $forceCollection = array_key_exists(Link::AS_COLLECTION, $representation)
                    ? (bool) $representation[Link::AS_COLLECTION]
                    : false;
                unset($representation[Link::AS_COLLECTION]);

                if (isset($byRelation[$rel])) {
                    $byRelation[$rel][] = $representation;
                } else {
                    $byRelation[$rel] = [$representation];
                }

                // If we're forcing a collection, and the current relation only
                // has one item, mark the relation to force a collection
                if (1 === count($byRelation[$rel]) && $forceCollection) {
                    $byRelation[$rel][Link::AS_COLLECTION] = true;
                }

                // If we have more than one link for the relation, and the
                // marker for forcing a collection is present, remove the
                // marker; it's redundant. Check for a count greater than 2,
                // as the marker itself will affect the count!
                if (2 < count($byRelation[$rel]) && isset($byRelation[$rel][Link::AS_COLLECTION])) {
                    unset($byRelation[$rel][Link::AS_COLLECTION]);
                }
            });

            return $byRelation;
        }, []);

        array_walk($relations, function ($links, $key) use (&$relations) {
            if (isset($relations[$key][Link::AS_COLLECTION])) {
                // If forcing a collection, do nothing to the links, but DO
                // remove the marker indicating a collection should be
                // returned.
                unset($relations[$key][Link::AS_COLLECTION]);
                return;
            }

            $relations[$key] = 1 === count($links) ? array_shift($links) : $links;
        });

        return $relations;
    }

    private function serializeEmbeddedResources()
    {
        $embedded = [];
        array_walk($this->embedded, function ($resource, $name) use (&$embedded) {
            $embedded[$name] = $resource instanceof self
                ? $resource->toArray()
                : array_map(function ($item) {
                    return $item->toArray();
                }, $resource);
        });

        return $embedded;
    }

    /**
     * @throws InvalidArgumentException if $a and $b are not structurally equivalent.
     */
    private function compareResources(self $a, self $b, string $name, string $context) : bool
    {
        $structureA = array_keys($a->getElements());
        $structureB = array_keys($b->getElements());
        sort($structureA);
        sort($structureB);
        if ($structureA !== $structureB) {
            throw new InvalidArgumentException(sprintf(
                '%s detected structurally inequivalent resources for element %s',
                $context,
                $name
            ));
        }
        return true;
    }
}
