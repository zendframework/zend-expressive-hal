<?php

namespace Hal;

use Closure;
use DOMDocument;
use DOMNode;
use Negotiation\Negotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class HalResponseFactory
{
    /**
     * @var string Default mediatype to use as the base Content-Type, minus the format.
     */
    const DEFAULT_CONTENT_TYPE = 'application/hal';

    // @codingStandardsIgnoreStart
    /**
     * @var int Default flags to use with json_encode()
     */
    const DEFAULT_JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;
    // @codingStandardsIgnoreEnd

    const NEGOTIATION_PRIORITIES = [
        'application/json',
        'application/*+json',
        'application/xml',
        'application/*+xml',
    ];

    /**
     * @var int
     */
    private $jsonFlags;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * Factory that, when called, returns a new, writable StreamInterface
     * instance to use as the response body.
     *
     * @var callable
     */
    private $streamFactory;

    public function __construct(
        int $jsonFlags = self::DEFAULT_JSON_FLAGS,
        ResponseInterface $responsePrototype = null,
        callable $streamFactory = null
    ) {
        $this->jsonFlags = $jsonFlags;
        $this->responsePrototype = $responsePrototype ?: new Response();
        $this->streamFactory = $streamFactory ?: Closure::fromCallable([$this, 'generateStream']);
    }

    public function createResponse(
        ServerRequestInterface $request,
        HalResource $resource,
        string $mediaType = self::DEFAULT_CONTENT_TYPE
    ) : ResponseInterface {
        $accept      = $request->getHeaderLine('Accept') ?: '*/*';
        $matchedType = (new Negotiator())->getBest($accept, self::NEGOTIATION_PRIORITIES);

        switch (true) {
            case (! $matchedType):
                $generator = Closure::fromCallable([$this, 'generateXmlResponse']);
                break;
            case (strstr($matchedType->getValue(), 'json')):
                $generator = Closure::fromCallable([$this, 'generateJsonResponse']);
                break;
            default:
                $generator = Closure::fromCallable([$this, 'generateXmlResponse']);
                break;
        }

        return $generator($resource, $mediaType);
    }

    /**
     * @throws Exception\InvalidResponseBodyException if the stream factory
     *     does not return a StreamInterface.
     * @throws Exception\InvalidResponseBodyException if the stream generated
     *     by the stream factory is not writable.
     */
    public function createStream() : StreamInterface
    {
        $stream = ($this->streamFactory)();
        if (! $body instanceof StreamInterface) {
            throw Exception\InvalidResponseBodyException::forIncorrectStreamType();
        }
        if (! $body->isWritable()) {
            throw Exception\InvalidResponseBodyException::forNonWritableStream();
        }
        return $stream;
    }

    private function generateStream() : Stream
    {
        return new Stream('php://temp', 'wb+');
    }

    private function generateJsonResponse(HalResource $resource, string $mediaType) : ResponseInterface
    {
        $body = ($this->streamFactory)();
        $body->write(json_encode($resource, $this->jsonFlags));
        return $this->responsePrototype
            ->withBody($body)
            ->withHeader('Content-Type', $mediaType . '+json');
    }

    private function generateXmlResponse(HalResource $resource, string $mediaType) : ResponseInterface
    {
        $body = ($this->streamFactory)();
        $body->write($this->createXmlPayload($resource));
        return $this->responsePrototype
            ->withBody($body)
            ->withHeader('Content-Type', $mediaType . '+xml');
    }

    private function createXmlPayload(HalResource $resource) : string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->appendChild($this->createResourceNode($dom, $resource->toArray()));
        return trim($dom->saveXML());
    }

    private function createResourceNode(DOMDocument $doc, array $resource, string $resourceRel = 'self') : DOMNode
    {
        // Normalize resource
        $resource['_links']    = $resource['_links'] ?? [];
        $resource['_embedded'] = $resource['_embedded'] ?? [];

        $node = $doc->createElement('resource');

        // Self-relational link attributes, if present and singular
        if (isset($resource['_links']['self']['href'])) {
            $node->setAttribute('rel', $resourceRel);
            $node->setAttribute('href', $resource['_links']['self']['href']);
            foreach ($resource['_links']['self'] as $attribute => $value) {
                if ($attribute === 'href') {
                    continue;
                }
                $node->setAttribute($attribute, $value);
            }
            unset($resource['_links']['self']);
        }

        foreach ($resource['_links'] as $rel => $linkData) {
            if ($this->isAssocArray($linkData)) {
                $node->appendChild($this->createLinkNode($doc, $rel, $linkData));
                continue;
            }

            foreach ($linkData as $linkDatum) {
                $node->appendChild($this->createLinkNode($doc, $rel, $linkDatum));
            }
        }
        unset($resource['_links']);

        foreach ($resource['_embedded'] as $rel => $childData) {
            if ($this->isAssocArray($childData)) {
                $node->appendChild($this->createResourceNode($doc, $childData, $rel));
                continue;
            }

            foreach ($childData as $childDatum) {
                $node->appendChild($this->createResourceNode($doc, $childDatum, $rel));
            }
        }
        unset($resource['_embedded']);

        return $this->createNodeTree($doc, $node, $resource);
    }

    private function createLinkNode(DOMDocument $doc, string $rel, array $data)
    {
        $link = $doc->createElement('link');
        $link->setAttribute('rel', $rel);
        foreach ($data as $key => $value) {
            $value = $this->normalizeConstantValue($value);
            $link->setAttribute($key, $value);
        }
        return $link;
    }

    /**
     * Convert true, false, and null to appropriate strings.
     *
     * In all other cases, return the value as-is.
     *
     * @param mixed $value
     * @return string|mixed
     */
    private function normalizeConstantValue($value)
    {
        $value = $value === true ? 'true' : $value;
        $value = $value === false ? 'false' : $value;
        $value = $value === null ? '' : $value;
        return $value;
    }

    private function isAssocArray(array $value) : bool
    {
        return array_values($value) !== $value;
    }

    /**
     * @return DOMNode|DOMNode[]
     */
    private function createResourceElement(DOMDocument $doc, string $name, $data)
    {
        if (is_scalar($data)) {
            $data = $this->normalizeConstantValue($data);
            return $doc->createElement($name, $data);
        }

        if (! is_array($data)) {
            throw Exception\InvalidResourceValueException::fromValue($data);
        }

        if ($this->isAssocArray($data)) {
            return $this->createNodeTree($doc, $doc->createElement($name), $data);
        }

        $elements = [];
        foreach ($value as $child) {
            $elements[] = $this->createResourceElement($doc, $name, $child);
        }
        return $elements;
    }

    private function createNodeTree(DOMDocument $doc, DOMNode $node, array $data) : DOMNode
    {
        foreach ($data as $key => $value) {
            $element = $this->createResourceElement($doc, $key, $value);
            if (! is_array($element)) {
                $node->appendChild($element);
                continue;
            }
            foreach ($element as $child) {
                $node->appendChild($child);
            }
        }

        return $node;
    }
}
