<?php

namespace Hal;

use Closure;
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

    /** @var Renderer\JsonRenderer */
    private $jsonRenderer;

    /** @var ResponseInterface */
    private $responsePrototype;

    /**
     * Factory that, when called, returns a new, writable StreamInterface
     * instance to use as the response body.
     *
     * @var callable
     */
    private $streamFactory;

    /** @var Renderer\XmlRenderer */
    private $xmlRenderer;

    public function __construct(
        int $jsonFlags = self::DEFAULT_JSON_FLAGS,
        ResponseInterface $responsePrototype = null,
        callable $streamFactory = null
    ) {
        $this->jsonRenderer = new Renderer\JsonRenderer($jsonFlags);
        $this->xmlRenderer = new Renderer\XmlRenderer();
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
            case ($matchedType && strstr($matchedType->getValue(), 'json')):
                $renderer = $this->jsonRenderer;
                $mediaType = $mediaType . '+json';
                break;
            case (! $matchedType):
                // fall-through
            default:
                $renderer = $this->xmlRenderer;
                $mediaType = $mediaType . '+xml';
                break;
        }

        $body = ($this->streamFactory)();
        $body->write($renderer->render($resource));
        return $this->responsePrototype
            ->withBody($body)
            ->withHeader('Content-Type', $mediaType);
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
}
