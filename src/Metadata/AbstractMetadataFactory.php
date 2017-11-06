<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

/**
 * TODO Would you prefer an interface?
 */
abstract class AbstractMetadataFactory
{
    /**
     * Creates the metadata out of the configuration
     *
     * TODO Would you prefer another method name (e. g. `createMetadata(array $metadata)`) ?
     *
     * @param array $metadata
     *
     * @return AbstractMetadata
     */
    abstract public function __invoke(array $metadata) : AbstractMetadata;
}
