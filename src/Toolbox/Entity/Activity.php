<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Safe\DateTime;
use Symfony\Component\Security\Core\User\UserInterface;
use WebDevelovers\ResourceBundle\Model\ResourceReferenceDoctrineFields;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Toolbox\Repository\ActivityRepository;
use WebDevelovers\ResourceModels\Invariant\TimestampableInterface;
use WebDevelovers\ResourceModels\Invariant\TimestampableTrait;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableInterface;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableTrait;
use WebDevelovers\ResourceModels\Nullable\DescriptionAwareInterface;
use WebDevelovers\ResourceModels\Nullable\DescriptionTrait;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'toolbox_activity')]
class Activity implements
    DescriptionAwareInterface,
    TimestampableInterface,
    UUIDIdentifiableInterface
{
    use DescriptionTrait;
    use ResourceReferenceDoctrineFields;
    use TimestampableTrait;
    use UUIDIdentifiableTrait;
    public function isDone(): bool
    {
        return $this->done;
    }

    public function done(): void
    {
        $this->done = true;
        $this->completionDate = new DateTime();
    }

    public function notDone(): void
    {
        $this->done = false;
        $this->completionDate = null;
    }

    public function __construct(
        string $summary,
        ActivityType $activityType,
        DateTimeInterface $dueDate,
        UserInterface $assignedTo,
        ResourceReference $resourceReference,
    ) {
        $this->initializeID();
        $this->initializeTimestamps();

        $this->summary = $summary;
        $this->activityType = $activityType;
        $this->dueDate = $dueDate;
        $this->assignedTo = $assignedTo;
        $this->done = false;

        $this->setFromResourceReference($resourceReference);
    }

    #[ORM\Column(type: Types::STRING, nullable: false)]
    public string $summary;

    #[ORM\ManyToOne(targetEntity: ActivityType::class)]
    #[ORM\JoinColumn(name: 'activity_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public ActivityType $activityType;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: false)]
    public DateTimeInterface $dueDate;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'assigned_to_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public UserInterface $assignedTo;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    public bool $done;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public string|null $feedback = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    public DateTimeInterface|null $completionDate = null;
}
