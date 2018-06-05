<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal;

use ArrayAccess;
use Psr\Container\ContainerInterface;
use Traversable;
use Zend\Expressive\Hal\ResourceGenerator\Exception\InvalidConfigException;
use Zend\Hydrator\HydratorPluginManager;

use function is_array;

class ResourceGeneratorFactory
{
    /** @var string */
    private $linkGeneratorServiceName;

    /**
     * Allow serialization
     */
    public static function __set_state(array $data) : self
    {
        return new self(
            $data['linkGeneratorServiceName'] ?? LinkGenerator::class
        );
    }

    /**
     * Allow varying behavior based on link generator service name.
     */
    public function __construct(string $linkGeneratorServiceName = LinkGenerator::class)
    {
        $this->linkGeneratorServiceName = $linkGeneratorServiceName;
    }

    public function __invoke(ContainerInterface $container) : ResourceGenerator
    {
        $generator = new ResourceGenerator(
            $container->get(Metadata\MetadataMap::class),
            $container->get(HydratorPluginManager::class),
            $container->get($this->linkGeneratorServiceName)
        );

        $this->injectStrategies($container, $generator);

        return $generator;
    }

    /**
     * @throws InvalidConfigException if the config service is not an array or
     *     ArrayAccess implementation.
     * @throws InvalidConfigException if the configured strategies value is not
     *     an array or traversable.
     */
    private function injectStrategies(ContainerInterface $container, ResourceGenerator $generator) : void
    {
        if (! $container->has('config')) {
            return;
        }

        $config = $container->get('config');

        if (! is_array($config) && ! $config instanceof ArrayAccess) {
            throw InvalidConfigException::dueToNonArray($config);
        }

        if (! isset($config['zend-expressive-hal']['resource-generator']['strategies'])) {
            return;
        }

        $strategies = $config['zend-expressive-hal']['resource-generator']['strategies'];

        if (! is_array($strategies) && ! $strategies instanceof Traversable) {
            throw InvalidConfigException::dueToInvalidStrategies($strategies);
        }

        foreach ($strategies as $metadataType => $strategy) {
            $generator->addStrategy(
                $metadataType,
                $container->get($strategy)
            );
        }
    }
}
