<?php

namespace HalTest\Renderer;

use Hal\Renderer\JsonRenderer;
use PHPUnit\Framework\TestCase;

class JsonRendererTest extends TestCase
{
    use TestAsset;

    public function testDelegatesToJsonEncode()
    {
        $renderer = new JsonRenderer();
        $resource = $this->createExampleResource();
        $expected = json_encode($resource, JsonRenderer::DEFAULT_JSON_FLAGS);

        $this->assertEquals($expected, $renderer->render($resource));
    }

    public function testRendersUsingJsonFlagsProvidedToConstructor()
    {
        $jsonFlags = 0;
        $renderer  = new JsonRenderer($jsonFlags);
        $resource  = $this->createExampleResource();
        $expected  = json_encode($resource, $jsonFlags);

        $this->assertEquals($expected, $renderer->render($resource));
    }
}
