<?php

namespace Hal\Metadata;

abstract class AbstractCollectionMetadata extends AbstractMetadata
{
    const TYPE_PLACEHOLDER = 'placeholder';
    const TYPE_QUERY = 'query';

    /** @var string */
    protected $collectionRelation;

    /** @var string */
    protected $paginationParam;

    /** @var string */
    protected $paginationParamType;

    public function getCollectionRelation() : string
    {
        return $this->collectionRelation;
    }

    public function getPaginationParam() : string
    {
        return $this->paginationParam;
    }

    public function getPaginationParamType() : string
    {
        return $this->paginationParamType;
    }
}
