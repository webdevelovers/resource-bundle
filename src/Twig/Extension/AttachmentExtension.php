<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use WebDevelovers\ResourceBundle\Twig\Runtime\AttachmentExtensionRuntime;

class AttachmentExtension extends AbstractExtension
{
    /** @return TwigFilter[] */
    public function getFilters(): array
    {
        return [
            new TwigFilter('human_readable_filesize', [AttachmentExtensionRuntime::class, 'humanReadableFilesize']),
        ];
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_icon', [AttachmentExtensionRuntime::class, 'fileIcon']),
            new TwigFunction('icon_color', [AttachmentExtensionRuntime::class, 'iconColor']),
        ];
    }
}
