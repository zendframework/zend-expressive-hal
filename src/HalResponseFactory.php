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
        Renderer\JsonRenderer $jsonRenderer = null,
        Renderer\XmlRenderer $xmlRenderer = null,
        ResponseInterface $responsePrototype = null,
        callable $streamFactory = null
    ) {
        $this->jsonRenderer = $jsonRenderer ?: new Renderer\JsonRenderer();
        $this->xmlRenderer = $xmlRenderer ?: new Renderer\XmlRenderer();
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
