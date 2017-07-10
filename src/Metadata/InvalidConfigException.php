<?php

namespace Hal\Metadata;

use RuntimeException;

class InvalidConfigException extends RuntimeException implements Exception
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

    public static function dueToUnrecognizedMetadataClass(string $class, string $classWithoutNamespace) : self
    {
        return new self(sprintf(
            '%s does not know how to construct a %s instance; please extend the '
            . 'factory and create a "create%s" method',
            MetadataMapFactory::class,
            $class,
            $classWithoutNamespace
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
