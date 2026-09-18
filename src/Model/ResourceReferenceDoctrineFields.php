<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\ResourceReference;

trait ResourceReferenceDoctrineFields
{
    #[ORM\Column(type: 'uuid', nullable: false)]
    protected(set) Uuid $subjectId;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    protected(set) string $subjectName;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    protected(set) string $resourceAlias;

    public function getResourceReference(): ResourceReference
    {
        return new ResourceReference($this->subjectId->toString(), $this->subjectName, $this->resourceAlias);
    }

    protected function setFromResourceReference(ResourceReference $resourceReference): void
    {
        $this->subjectId = Uuid::fromString($resourceReference->subjectId);
        $this->subjectName = $resourceReference->subjectName;
        $this->resourceAlias = $resourceReference->resourceAlias;
    }
}
