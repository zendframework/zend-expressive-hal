<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata\Exception;

use RuntimeException;
use Zend\Expressive\Hal\Metadata\AbstractMetadata;
use Zend\Expressive\Hal\Metadata\MetadataFactoryInterface;
use Zend\Expressive\Hal\Metadata\MetadataMap;
use Zend\Expressive\Hal\Metadata\MetadataMapFactory;

use function get_class;
use function gettype;
use function implode;
use function is_object;
use function is_string;
use function sprintf;

class InvalidConfigException extends RuntimeException implements ExceptionInterface
{
    /**
     * @param mixed $config
     */
    public static function dueToNonArray($config) : self
    {
        return new self(sprintf(
            'Invalid %s configuration; expected an array, but received %s',
            MetadataMap::class,
            is_object($config) ? get_class($config) : gettype($config)
        ));
    }

    public static function dueToNonArrayMetadata($metadata) : self
    {
        return new self(sprintf(
            'Invalid %s metadata item configuration; expected an array, but received %s',
            MetadataMap::class,
            is_object($metadata) ? get_class($metadata) : gettype($metadata)
        ));
    }

    public static function dueToMissingMetadataClass() : self
    {
        return new self('Unable to generate metadata; missing "__class__" element');
    }

    /**
     * @param mixed $class
     */
    public static function dueToInvalidMetadataClass($class) : self
    {
        $className = $class;
        if (! is_string($className)) {
            $className = is_object($class) ? get_class($class) : gettype($class);
        }
        return new self(sprintf(
            'Invalid metadata class provided: %s is not a class name',
            $className
        ));
    }

    public static function dueToNonMetadataClass(string $class) : self
    {
        return new self(sprintf(
            '%s is not a valid metadata class; does not extend %s',
            $class,
            AbstractMetadata::class
        ));
    }

    public static function dueToInvalidMetadataFactoryClass(string $class) : self
    {
        return new self(sprintf(
            '%s is not a valid metadata factory class; does not implement %s',
            $class,
            MetadataFactoryInterface::class
        ));
    }

    public static function dueToUnrecognizedMetadataClass(string $class) : self
    {
        return new self(sprintf(
            '%s does not know how to construct a %s instance; please provide a '
            . 'factory in your configuration',
            MetadataMapFactory::class,
            $class
        ));
    }

    public static function dueToMissingMetadata(string $type, array $requiredKeys) : self
    {
        return new self(sprintf(
            'Unable to create HAL metadata of type %s; one or more of the '
            . 'following keys were missing: %s',
            $type,
            implode(', ', $requiredKeys)
        ));
    }
}
