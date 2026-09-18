<?php

declare(strict_types=1);


use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\ResourceReference;

class ResourceReferenceTest extends TestCase
{
    public function testResourceReference(): void
    {
        $reference = new ResourceReference('123', 'Subject Name', 'app.resource');

        $this->assertSame('123', $reference->subjectId);
        $this->assertSame('Subject Name', $reference->subjectName);
        $this->assertSame('app.resource', $reference->resourceAlias);
    }
}
