<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Blame;

readonly class Blame
{
    public function __construct(
        public string      $userId,
        public string      $userIdentifier,
        public string|null $userFirewall = null,
        public string|null $ip = null,
    ) {
    }
}
