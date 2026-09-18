<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'Toolbox:Toolbox', template: '@WebDeveloversResource/components/toolbox/Toolbox.html.twig')]
final class Toolbox
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $resourceID;

    #[LiveProp]
    public string $resourceName;

    #[LiveProp]
    public string $resourceType;

    #[LiveProp]
    public string|null $section;
}
