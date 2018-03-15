<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

use function class_exists;

class MetadataMap
{
    private $map = [];

    /**
     * @throws Exception\DuplicateMetadataException if metadata matching the
     *     class of the provided metadata already exists in the map.
     * @throws Exception\UndefinedClassException if the class in the provided
     *     metadata does not exist.
     */
    public function add(AbstractMetadata $metadata) : void
    {
        $class = $metadata->getClass();
        if (isset($this->map[$class])) {
            throw Exception\DuplicateMetadataException::create($class);
        }

        if (! class_exists($class)) {
            throw Exception\UndefinedClassException::create($class);
        }

        $this->map[$class] = $metadata;
    }

    public function has(string $class) : bool
    {
        return isset($this->map[$class]);
    }

    /**
     * @throws Exception\UndefinedMetadataException if no metadata matching the
     *     provided class is found in the map.
     */
    public function get(string $class) : AbstractMetadata
    {
        if (! isset($this->map[$class])) {
            throw Exception\UndefinedMetadataException::create($class);
        }

        return $this->map[$class];
    }
}
