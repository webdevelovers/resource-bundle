<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface CurrentUserProviderInterface
{
    public function getUser(): UserInterface|null;

    public function requireUser(): UserInterface;
}
