<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use WebDevelovers\ResourceBundle\Model\ResourceReferenceDoctrineFields;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceModels\Invariant\TimestampableInterface;
use WebDevelovers\ResourceModels\Invariant\TimestampableTrait;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableInterface;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableTrait;

#[ORM\Entity]
#[ORM\Table(name: 'toolbox_bookmark')]
class Bookmark implements
    TimestampableInterface,
    UUIDIdentifiableInterface
{
    use ResourceReferenceDoctrineFields;
    use TimestampableTrait;
    use UUIDIdentifiableTrait;

    public function __construct(
        UserInterface $user,
        ResourceReference $resourceReference,
    ) {
        $this->initializeID();
        $this->initializeTimestamps();

        $this->user = $user;

        $this->setFromResourceReference($resourceReference);
    }

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public UserInterface $user;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public string|null $section = null;
}
