<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

/**
 * Interface describing factories that create metadata instances.
 */
interface MetadataFactoryInterface
{
    /**
     * Creates a Metadata based on the MetadataMap configuration.
     *
     * @param string $requestedName The requested name of the metadata type
     * @param array  $metadata      The metadata should have the following structure:
     *     <code>
     *     [
     *         '__class__' => 'Fully qualified class name of an AbstractMetadata type',
     *         // additional key/value pairs as required by the metadata type.
     *     ]
     *     </code>
     *
     *     The '__class__' key decides which AbstractMetadata should be used
     *     (and which corresponding factory will be called to create it).
     * @return AbstractMetadata
     */
    public function createMetadata(string $requestedName, array $metadata) : AbstractMetadata;
}
