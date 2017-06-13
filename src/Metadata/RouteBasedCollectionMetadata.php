<?php

namespace Hal\Metadata;

class RouteBasedCollectionMetadata extends AbstractCollectionMetadata
{
    /** @var string */
    private $route;

    /** @var array */
    private $queryStringArguments;

    public function __construct(
        string $class,
        string $collectionElementName,
        string $route,
        string $paginationParam,
        string $paginationParamType,
        array $queryStringArguments = []
    ) {
        $this->class = $class;
        $this->collectionElementName = $collectionElementName;
        $this->route = $route;
        $this->paginationParam = $paginationParam;
        $this->paginationParamType = $paginationParamType;
        $this->queryStringArguments = $queryStringArguments;
    }

    public function getRoute() : string
    {
        return $this->route;
    }

    public function getQueryStringArguments() : array
    {
        return $this->queryStringArguments;
    }
}
