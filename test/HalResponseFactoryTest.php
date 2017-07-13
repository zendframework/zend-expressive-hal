<?php

namespace HalTest;

use Hal\HalResource;
use Hal\HalResponseFactory;
use Hal\Link;
use Hal\Renderer;
use HalTest\Renderer\TestAsset;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;

class HalResponseFactoryTest extends TestCase
{
    use TestAsset;

    public function setUp()
    {
        $this->request      = $this->prophesize(ServerRequestInterface::class);
        $this->jsonRenderer = $this->prophesize(Renderer\JsonRenderer::class);
        $this->xmlRenderer  = $this->prophesize(Renderer\XmlRenderer::class);
        $this->factory      = new HalResponseFactory(
            null,
            null,
            $this->jsonRenderer->reveal(),
            $this->xmlRenderer->reveal()
        );
    }

    public function testReturnsJsonResponseIfNoAcceptHeaderPresent()
    {
        $resource = $this->createExampleResource();
        $this->jsonRenderer->render($resource)->willReturn('{}');
        $this->xmlRenderer->render($resource)->shouldNotBeCalled();
        $this->request->getHeaderLine('Accept')->willReturn('');
        $response = $this->factory->createResponse(
            $this->request->reveal(),
            $resource
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertContains('application/hal+json', $response->getHeaderLine('Content-Type'));
        $json = (string) $response->getBody();
        $this->assertEquals('{}', $json);
    }

    public function jsonAcceptHeaders()
    {
        return [
            'application/json'             => ['application/json'],
            'application/hal+json'         => ['application/hal+json'],
            'application/vnd.example+json' => ['application/vnd.example+json'],
        ];
    }

    /**
     * @dataProvider jsonAcceptHeaders
     */
    public function testReturnsJsonResponseIfAcceptHeaderMatchesJson(string $header)
    {
        $resource = $this->createExampleResource();
        $this->jsonRenderer->render($resource)->willReturn('{}');
        $this->xmlRenderer->render($resource)->shouldNotBeCalled();
        $this->request->getHeaderLine('Accept')->willReturn($header);
        $response = $this->factory->createResponse(
            $this->request->reveal(),
            $resource
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertContains('application/hal+json', $response->getHeaderLine('Content-Type'));
        $json = (string) $response->getBody();
        $this->assertEquals('{}', $json);
    }

    public function xmlAcceptHeaders()
    {
        return [
            'application/xml'             => ['application/xml'],
            'application/xhtml+xml'       => ['application/xhtml+xml'],
            'application/hal+xml'         => ['application/hal+xml'],
            'application/vnd.example+xml' => ['application/vnd.example+xml'],
        ];
    }

    /**
     * @dataProvider xmlAcceptHeaders
     */
    public function testReturnsXmlResponseIfAcceptHeaderMatchesXml(string $header)
    {
        $resource = $this->createExampleResource();
        $this->xmlRenderer->render($resource)->willReturn('<resource/>');
        $this->jsonRenderer->render($resource)->shouldNotBeCalled();
        $this->request->getHeaderLine('Accept')->willReturn($header);
        $response = $this->factory->createResponse(
            $this->request->reveal(),
            $resource
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertContains('application/hal+xml', $response->getHeaderLine('Content-Type'));
        $xml = (string) $response->getBody();
        $this->assertEquals('<resource/>', $xml);
    }

    public function customMediaTypes()
    {
        // @codingStandardsIgnoreStart
        return [
            'json' => ['application/json', 'application/vnd.example', '{}', 'application/vnd.example+json'],
            'xml'  => ['application/xml',  'application/vnd.example', '<resource/>',  'application/vnd.example+xml'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider customMediaTypes
     */
    public function testUsesProvidedMediaTypeInReturnedResponseWithMatchedFormatAppended(
        string $header,
        string $mediaType,
        string $responseBody,
        string $expectedMediaType
    ) {
        $resource = $this->createExampleResource();
        switch (true) {
            case (strstr($header, 'json')):
                $this->jsonRenderer->render($resource)->willReturn($responseBody);
                $this->xmlRenderer->render($resource)->shouldNotBeCalled();
                break;
            case (strstr($header, 'xml')):
                $this->xmlRenderer->render($resource)->willReturn($responseBody);
                $this->jsonRenderer->render($resource)->shouldNotBeCalled();
                // fall-through
            default:
                break;
        }
        $this->request->getHeaderLine('Accept')->willReturn($header);
        $response = $this->factory->createResponse(
            $this->request->reveal(),
            $resource,
            $mediaType
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertContains($expectedMediaType, $response->getHeaderLine('Content-Type'));
        $payload = (string) $response->getBody();
        $this->assertEquals($responseBody, $payload);
    }

    public function testAllowsProvidingResponsePrototypeToConstructor()
    {
        $resource = $this->createExampleResource();

        $prototype = $this->prophesize(ResponseInterface::class);
        $prototype->withBody(Argument::type(StreamInterface::class))->will([$prototype, 'reveal']);
        $prototype->withHeader('Content-Type', 'application/hal+json')->will([$prototype, 'reveal']);

        $this->jsonRenderer->render($resource)->willReturn('{}');
        $this->xmlRenderer->render($resource)->shouldNotBeCalled();
        $this->request->getHeaderLine('Accept')->willReturn('application/json');

        $factory = new HalResponseFactory(
            $prototype->reveal(),
            null,
            $this->jsonRenderer->reveal(),
            $this->xmlRenderer->reveal()
        );
        $response = $factory->createResponse(
            $this->request->reveal(),
            $resource
        );

        $this->assertSame($prototype->reveal(), $response);
    }

    public function testAllowsProvidingStreamFactoryToConstructor()
    {
        $resource = $this->createExampleResource();

        $this->jsonRenderer->render($resource)->willReturn('{}');
        $this->xmlRenderer->render($resource)->shouldNotBeCalled();
        $this->request->getHeaderLine('Accept')->willReturn('application/json');

        $stream = $this->prophesize(StreamInterface::class);
        $stream->write('{}')->shouldBeCalled();

        $streamFactory = function () use ($stream) {
            return $stream->reveal();
        };

        $factory = new HalResponseFactory(
            null,
            $streamFactory,
            $this->jsonRenderer->reveal(),
            $this->xmlRenderer->reveal()
        );

        $response = $factory->createResponse(
            $this->request->reveal(),
            $resource
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertContains('application/hal+json', $response->getHeaderLine('Content-Type'));
    }
}
