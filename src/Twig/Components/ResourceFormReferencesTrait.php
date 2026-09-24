<?php

namespace WebDevelovers\ResourceBundle\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

trait ResourceFormReferencesTrait
{
    #[LiveProp]
    public string $formID;
}