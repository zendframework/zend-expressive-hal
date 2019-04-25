<?php
declare(strict_types=1);

namespace Zend\Expressive\Hal\Metadata;

interface MetadataInterface
{
    /**
     * Returns the configured metadata class name
     *
     * @return string
     */
    public function getClass() : string;

    /**
     * Determines whenever the current depth level exceeds the allowed max depth
     *
     * @param int $currentDepth
     *
     * @return bool
     */
    public function hasReachedMaxDepth(int $currentDepth): bool;
}
