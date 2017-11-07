<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

use Psr\Container\ContainerInterface;

/**
 * Create a MetadataMap based on configuration.
 *
 * Utilizes the "config" service, and pulls the subkey `Hal\Metadata\MetadataMap`.
 * Each entry is expected to be an associative array, with the following
 * structure:
 *
 * <code>
 * [
 *     // Fully qualified class name of an AbstractMetadata type
 *     '__class__' => MyMetadata::class,
 *
 *     // additional key/value pairs as required by the metadata type. (See their respective factories)
 * ]
 * </code>
 *
 * If you have created a custom metadata type, you have to register a factory
 * in your configuration to support it. Add an entry to the config array:
 * $config['zend-expressive-hal']['metadata-factories'][MyMetadata::class] = MyMetadataFactory::class.
 *
 * This factory should implement the `MetadataFactoryInterface`.
 */
class MetadataMapFactory
{
    public function __invoke(ContainerInterface $container) : MetadataMap
    {
        $config            = $container->has('config') ? $container->get('config') : [];
        $metadataMapConfig = $config[MetadataMap::class] ?? [];

        if (! is_array($metadataMapConfig)) {
            throw Exception\InvalidConfigException::dueToNonArray($metadataMapConfig);
        }

        $metadataFactories = $config['zend-expressive-hal']['metadata-factories'] ?? [];

        return $this->populateMetadataMapFromConfig(
            new MetadataMap(),
            $metadataMapConfig,
            $metadataFactories
        );
    }

    private function populateMetadataMapFromConfig(
        MetadataMap $metadataMap,
        array $metadataMapConfig,
        array $metadataFactories
    ) : MetadataMap {
        foreach ($metadataMapConfig as $metadata) {
            if (! is_array($metadata)) {
                throw Exception\InvalidConfigException::dueToNonArrayMetadata($metadata);
            }

            $this->injectMetadata($metadataMap, $metadata, $metadataFactories);
        }

        return $metadataMap;
    }

    /**
     * @throws Exception\InvalidConfigException if the metadata is missing a
     *     "__class__" entry.
     * @throws Exception\InvalidConfigException if the "__class__" entry is not
     *     a class.
     * @throws Exception\InvalidConfigException if the "__class__" entry is not
     *     an AbstractMetadata class.
     * @throws Exception\InvalidConfigException if no matching `create*()`
     *     method is found for the "__class__" entry.
     */
    private function injectMetadata(MetadataMap $metadataMap, array $metadata, array $metadataFactories)
    {
        if (! isset($metadata['__class__'])) {
            throw Exception\InvalidConfigException::dueToMissingMetadataClass();
        }

        if (! class_exists($metadata['__class__'])) {
            throw Exception\InvalidConfigException::dueToInvalidMetadataClass($metadata['__class__']);
        }

        $metadataClass = $metadata['__class__'];
        if (! in_array(AbstractMetadata::class, class_parents($metadataClass), true)) {
            throw Exception\InvalidConfigException::dueToNonMetadataClass($metadataClass);
        }

        if (isset($metadataFactories[$metadataClass])) {
            // A factory was registered. Use it!
            $metadataInstance = $this->createMetadataViaFactoryClass(
                $metadataClass,
                $metadata,
                $metadataFactories[$metadataClass]
            );
        } else {
            // No factory was not registered. Try to use the deprecated factory method
            $metadataInstance = $this->createMetadataViaFactoryMethod(
                $metadataClass,
                $metadata
            );
        }

        $metadataMap->add($metadataInstance);
    }

    /**
     * Uses the registered factory class to create the metadata
     *
     * @param string $factoryClass
     * @param array  $metadata
     *
     * @return AbstractMetadata
     */
    private function createMetadataViaFactoryClass(
        string $metadataClass,
        array $metadata,
        string $factoryClass
    ) : AbstractMetadata {
        if (! in_array(MetadataFactoryInterface::class, class_parents($factoryClass), true)) {
            throw Exception\InvalidConfigException::dueToInvalidMetadataFactoryClass($factoryClass);
        }

        $factory = new $factoryClass();
        /* @var $factory MetadataFactoryInterface */
        return $factory->createMetadata($metadataClass, $metadata);
    }

    /**
     * Call the factory method of this class namend "createMyMetadata(array $metadata)"
     *
     * @param string $metadataClass
     * @param array  $metadata
     *
     * @return AbstractMetadata
     */
    private function createMetadataViaFactoryMethod(string $metadataClass, array $metadata): AbstractMetadata
    {
        $normalizedClass = $this->stripNamespaceFromClass($metadataClass);
        $method          = sprintf('create%s', $normalizedClass);

        if (! method_exists($this, $method)) {
            throw Exception\InvalidConfigException::dueToUnrecognizedMetadataClass($metadataClass);
        }

        return $this->$method($metadata);
    }

    private function stripNamespaceFromClass(string $class) : string
    {
        $segments = explode('\\', $class);
        return array_pop($segments);
    }
}
