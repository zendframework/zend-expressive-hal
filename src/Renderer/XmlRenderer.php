<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\Renderer;

use DateTimeInterface;
use DOMDocument;
use DOMNode;
use Zend\Expressive\Hal\Exception;
use Zend\Expressive\Hal\HalResource;

use function array_values;
use function is_array;
use function is_object;
use function is_scalar;
use function method_exists;
use function trim;

class XmlRenderer implements RendererInterface
{
    public function render(HalResource $resource) : string
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
     * Convert true and false to appropriate strings.
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
        if ($data === null) {
            return $doc->createElement($name, $data);
        }

        if (is_scalar($data)) {
            $data = $this->normalizeConstantValue($data);
            return $doc->createElement($name, $data);
        }

        if (is_object($data)) {
            $data = $this->createDataFromObject($data);
            return $doc->createElement($name, $data);
        }

        if (! is_array($data)) {
            throw Exception\InvalidResourceValueException::fromValue($data);
        }

        if ($this->isAssocArray($data)) {
            return $this->createNodeTree($doc, $doc->createElement($name), $data);
        }

        $elements = [];
        foreach ($data as $child) {
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

    /**
     * @todo Detect JsonSerializable, and pass to
     *     json_decode(json_encode($object), true), passing the final value
     *     back to createResourceElement()?
     * @param object $object
     * @throws Exception\InvalidResourceValueException if unable to serialize
     *     the data to a string.
     */
    private function createDataFromObject($object) : string
    {
        if ($object instanceof DateTimeInterface) {
            return $object->format('c');
        }

        if (! method_exists($object, '__toString')) {
            throw Exception\InvalidResourceValueException::fromObject($object);
        }

        return (string) $object;
    }
}
