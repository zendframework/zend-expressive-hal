<?php

namespace HalTest;

use Hal\Link;
use Hal\Resource;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function testCanConstructWithData()
    {
        $this->markTestIncomplete();
    }

    public function testInvalidDataNamesRaiseExceptionsDuringConstruction()
    {
        $this->markTestIncomplete();
    }

    public function testCanConstructWithDataContainingEmbeddedResources()
    {
        $this->markTestIncomplete();
    }

    public function testCanConstructWithLinks()
    {
        $this->markTestIncomplete();
    }

    public function testNonLinkItemsRaiseExceptionDuringConstruction()
    {
        $this->markTestIncomplete();
    }

    public function testCanConstructWithEmbeddedResources()
    {
        $this->markTestIncomplete();
    }

    public function testNonResourceOrCollectionItemsRaiseExceptionDuringConstruction()
    {
        $this->markTestIncomplete();
    }

    public function testInvalidResourceNamesRaiseExceptionsDuringConstruction()
    {
        $this->markTestIncomplete();
    }

    public function testWithLinkReturnsNewInstanceContainingNewLink()
    {
        $this->markTestIncomplete();
    }

    public function testWithLinkReturnsSameInstanceIfAlreadyContainsLinkInstance()
    {
        $this->markTestIncomplete();
    }

    public function testWithoutLinkReturnsNewInstanceRemovingLink()
    {
        $this->markTestIncomplete();
    }

    public function testWithoutLinkReturnsSameInstanceIfLinkIsNotPresent()
    {
        $this->markTestIncomplete();
    }

    public function testGetLinksByRelReturnsAllLinksWithGivenRelationshipAsArray()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementRaisesExceptionForInvalidName()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementRaisesExceptionIfNameCollidesWithExistingResource()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementReturnsNewInstanceWithNewElement()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementReturnsNewInstanceOverwritingExistingElementValue()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementProxiesToEmbedIfResourceValueProvided()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementProxiesToEmbedIfResourceCollectionValueProvided()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedRaisesExceptionForInvalidName()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedRaisesExceptionIfNameCollidesWithExistingData()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedReturnsNewInstanceWithEmbeddedResource()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedReturnsNewInstanceWithEmbeddedCollection()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedReturnsNewInstanceAppendingResourceToExistingResource()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedReturnsNewInstanceAppendingResourceToExistingCollection()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedReturnsNewInstanceAppendingCollectionToExistingCollection()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedRaisesExceptionIfNewResourceDoesNotMatchStructureOfExisting()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedRaisesExceptionIfNewResourceDoesNotMatchCollectionResourceStructure()
    {
        $this->markTestIncomplete();
    }

    public function testEmbedRaisesExceptionIfResourcesInCollectionAreNotOfSameStructure()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementsAddsNewDataToNewResourceInstance()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementsAddsNewEmbeddedResourcesToNewResourceInstance()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementsOverwritesExistingDataInNewResourceInstance()
    {
        $this->markTestIncomplete();
    }

    public function testWithElementsAppendsEmbeddedResourcesToExistingResourcesInNewResourceInstance()
    {
        $this->markTestIncomplete();
    }

    public function testToArrayReturnsHalDataStructure()
    {
        $this->markTestIncomplete();
    }

    public function testJsonSerializeReturnsHalDataStructure()
    {
        $this->markTestIncomplete();
    }
}
