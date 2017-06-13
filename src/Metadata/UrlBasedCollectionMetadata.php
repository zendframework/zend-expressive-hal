<?php

namespace Hal\Metadata;

use InvalidArgumentException;

class UrlBasedCollectionMetadata extends AbstractCollectionMetadata
{
    /**
     * URL to use for the `self` relation of the collection.
     * @var string
     */
    private $url;

    public function __construct(
        string $class,
        string $collectionElementName,
        string $url,
        string $paginationParam = 'page',
        string $paginationParamType = self::TYPE_QUERY
    ) {
        if (empty($collectionElementName)) {
            throw new InvalidArgumentException('$collectionElementName MUST NOT be empty');
        }

        if (empty($paginationParam)) {
            throw new InvalidArgumentException('$paginationParam MUST NOT be empty');
        }

        if (! in_array($paginationParamType, [self::TYPE_PLACEHOLDER, self::TYPE_QUERY], true)) {
            throw new InvalidArgumentException(sprintf(
                '$paginationParamType MUST be one of "%s" or "%s"; received "%s"',
                self::TYPE_PLACEHOLDER,
                self::TYPE_QUERY,
                $paginationParamType
            ));
        }

        $this->class                 = $class;
        $this->collectionElementName = $collectionElementName;
        $this->url                   = $url;
        $this->paginationParam       = $paginationParam;
        $this->paginationParamType   = $paginationParamType;
    }

    public function getUrl() : string
    {
        return $this->url;
    }
}
