<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Blame;

interface BlameGeneratorInterface
{
    public const string SYSTEM_UUID = '01a0b412-b8b9-77fb-a637-d9110383768f';

    public function generate(): Blame;
}
