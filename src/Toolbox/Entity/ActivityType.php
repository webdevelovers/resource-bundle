<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use WebDevelovers\ResourceModels\Invariant\NamedInterface;
use WebDevelovers\ResourceModels\Invariant\NamedSluggableTrait;
use WebDevelovers\ResourceModels\Invariant\SluggableInterface;
use WebDevelovers\ResourceModels\Invariant\TimestampableInterface;
use WebDevelovers\ResourceModels\Invariant\TimestampableTrait;
use WebDevelovers\ResourceModels\Invariant\ToggleableInterface;
use WebDevelovers\ResourceModels\Invariant\ToggleableTrait;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableInterface;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableTrait;
use WebDevelovers\ResourceModels\Nullable\DescriptionAwareInterface;
use WebDevelovers\ResourceModels\Nullable\DescriptionTrait;

#[ORM\Entity]
#[ORM\Table(name: 'toolbox_activity_type')]
class ActivityType implements
    DescriptionAwareInterface,
    NamedInterface,
    SluggableInterface,
    TimestampableInterface,
    ToggleableInterface,
    UUIDIdentifiableInterface
{
    use DescriptionTrait;
    use NamedSluggableTrait;
    use TimestampableTrait;
    use ToggleableTrait;
    use UUIDIdentifiableTrait;

    public function __construct(string $name, int $sequence, string $icon)
    {
        $this->initializeID();
        $this->initializeTimestamps();
        $this->enable();

        $this->name = $name;
        $this->sequence = $sequence;
        $this->icon = $icon;
    }

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    public int $sequence;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    public string $icon;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public string|null $resource = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public string|null $defaultSummary = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'assigned_to_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public UserInterface|null $defaultAssignedTo = null;
}
