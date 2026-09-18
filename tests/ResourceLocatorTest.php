<?php

declare(strict_types=1);


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\ResourceLocator;
use WebDevelovers\ResourceBundle\ResourceReference;

class ResourceLocatorTest extends TestCase
{
    public function testGetResource(): void
    {
        $registry = $this->createMock(MetadataRegistryInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(MetadataInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $resource = $this->createStub(ResourceInterface::class);

        $reference = new ResourceReference('123', 'Subject Name', 'app.resource');

        $registry->expects($this->once())->method('get')->with('app.resource')->willReturn($metadata);
        $metadata->expects($this->once())->method('getClass')->with('model')->willReturn('App\Entity\SomeResource');
        $entityManager->expects($this->once())->method('getRepository')->with('App\Entity\SomeResource')->willReturn($repository);
        $repository->expects($this->once())->method('find')->with('123')->willReturn($resource);

        $locator = new ResourceLocator($registry, $entityManager);
        $result = $locator->getResource($reference);

        $this->assertSame($resource, $result);
    }
}
