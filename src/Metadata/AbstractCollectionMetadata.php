<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

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
