<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Metadata;

use InvalidArgumentException;

use function in_array;
use function sprintf;

class UrlBasedCollectionMetadata extends AbstractCollectionMetadata
{
    /**
     * URL to use for the `self` relation of the collection.
     * @var string
     */
    private $url;

    public function __construct(
        string $class,
        string $collectionRelation,
        string $url,
        string $paginationParam = 'page',
        string $paginationParamType = self::TYPE_QUERY
    ) {
        if (empty($collectionRelation)) {
            throw new InvalidArgumentException('$collectionRelation MUST NOT be empty');
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

        $this->class               = $class;
        $this->collectionRelation  = $collectionRelation;
        $this->url                 = $url;
        $this->paginationParam     = $paginationParam;
        $this->paginationParamType = $paginationParamType;
    }

    public function getUrl() : string
    {
        return $this->url;
    }
}
