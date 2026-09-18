<?php

namespace WebDevelovers\ResourceBundle\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Blame\Blame;

trait BlameDoctrineFields
{
    #[ORM\Column(type: 'uuid', nullable: false)]
    public Uuid $blameId;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    public string $blameUserIdentifier;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    public string $blameUserFirewall;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    public string $ip;

    protected function setFromBlame(Blame $blame): void
    {
        $this->blameId = Uuid::fromString($blame->userId);
        $this->blameUserIdentifier = $blame->userIdentifier;
        $this->blameUserFirewall = $blame->userFirewall;
        $this->ip = $blame->ip;
    }
}