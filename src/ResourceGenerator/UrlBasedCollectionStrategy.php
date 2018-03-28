<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\ResourceGenerator;

use Psr\Http\Message\ServerRequestInterface;
use Traversable;
use Zend\Expressive\Hal\HalResource;
use Zend\Expressive\Hal\Link;
use Zend\Expressive\Hal\Metadata;
use Zend\Expressive\Hal\ResourceGenerator;

use function get_class;
use function http_build_query;
use function parse_str;
use function parse_url;
use function preg_replace;
use function sprintf;
use function str_replace;

use const PHP_URL_FRAGMENT;
use const PHP_URL_QUERY;

class UrlBasedCollectionStrategy implements StrategyInterface
{
    use ExtractCollectionTrait;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        if (! $metadata instanceof Metadata\UrlBasedCollectionMetadata) {
            throw Exception\UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\UrlBasedCollectionMetadata::class
            );
        }

        if (! $instance instanceof Traversable) {
            throw Exception\InvalidCollectionException::fromInstance($instance, get_class($this));
        }

        return $this->extractCollection($instance, $metadata, $resourceGenerator, $request);
    }

    /**
     * @param string $rel Relation to use when creating Link
     * @param int $page Page number for generated link
     * @param Metadata\AbstractCollectionMetadata $metadata Used to provide the
     *     base URL, pagination parameter, and type of pagination used (query
     *     string, path parameter)
     * @param ResourceGenerator $resourceGenerator Ignored; required to fulfill
     *     abstract.
     * @param ServerRequestInterface $request Ignored; required to fulfill
     *     abstract.
     * @return Link
     */
    protected function generateLinkForPage(
        string $rel,
        int $page,
        Metadata\AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : Link {
        $paginationParam = $metadata->getPaginationParam();
        $paginationType = $metadata->getPaginationParamType();
        $url = $metadata->getUrl() . '?' . http_build_query($request->getQueryParams());

        switch ($paginationType) {
            case Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER:
                $url = str_replace($url, $paginationParam, $page);
                break;
            case Metadata\AbstractCollectionMetadata::TYPE_QUERY:
                // fall-through
            default:
                $url = $this->stripUrlFragment($url);
                $url = $this->appendPageQueryToUrl($url, $page, $paginationParam);
        }

        return new Link($rel, $url);
    }

    /**
     * @param Metadata\AbstractCollectionMetadata $metadata Provides base URL
     *     for self link.
     * @param ResourceGenerator $resourceGenerator Ignored; required to fulfill
     *     abstract.
     * @param ServerRequestInterface $request Ignored; required to fulfill
     *     abstract.
     * @return Link
     */
    protected function generateSelfLink(
        Metadata\AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) {

        $queryStringArgs = $request->getQueryParams();
        $url = $metadata->getUrl();
        if ($queryStringArgs !== null) {
            $url .= '?' . http_build_query($queryStringArgs);
        }

        return new Link('self', $url);
    }

    private function stripUrlFragment(string $url) : string
    {
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        if (null === $fragment) {
            // parse_url returns null both for absence of fragment and empty fragment
            return preg_replace('/#$/', '', $url);
        }

        return str_replace('#' . $fragment, '', $url);
    }

    private function appendPageQueryToUrl(string $url, int $page, string $paginationParam) : string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (null === $query) {
            // parse_url returns null both for absence of query and empty query
            $url = preg_replace('/\?$/', '', $url);
            return sprintf('%s?%s=%s', $url, $paginationParam, $page);
        }

        parse_str($query, $qsa);
        $qsa[$paginationParam] = $page;

        return str_replace('?' . $query, '?' . http_build_query($qsa), $url);
    }
}
