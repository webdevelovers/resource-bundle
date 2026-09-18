<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use WebDevelovers\ResourceBundle\Blame\Blame;
use WebDevelovers\ResourceBundle\Model\BlameDoctrineFields;
use WebDevelovers\ResourceBundle\Model\ResourceReferenceDoctrineFields;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Toolbox\Repository\TimelineEntryRepository;
use WebDevelovers\ResourceModels\Invariant\TimestampableInterface;
use WebDevelovers\ResourceModels\Invariant\TimestampableTrait;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableInterface;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableTrait;

#[ORM\Entity(repositoryClass: TimelineEntryRepository::class)]
#[ORM\Table(name: 'toolbox_timeline_entry')]
class TimelineEntry implements
    TimestampableInterface,
    UUIDIdentifiableInterface
{
    use BlameDoctrineFields;
    use ResourceReferenceDoctrineFields;
    use TimestampableTrait;
    use UUIDIdentifiableTrait;

    /** @param array<string, mixed> $metadata */
    public function __construct(
        string $event,
        ResourceReference $resourceReference,
        Blame $blame,
        array $metadata = [],
    ) {
        $this->initializeID();
        $this->initializeTimestamps();

        $this->event = $event;
        $this->metadata = $metadata;

        $this->setFromBlame($blame);
        $this->setFromResourceReference($resourceReference);
    }

    #[ORM\Column(type: Types::STRING, nullable: false)]
    public string $event;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    public array $metadata;
}
