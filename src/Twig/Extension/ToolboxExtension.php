<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use WebDevelovers\ResourceBundle\Twig\Runtime\ToolboxExtensionRuntime;

class ToolboxExtension extends AbstractExtension
{
    /** @return TwigFilter[] */
    public function getFilters(): array
    {
        return [
            new TwigFilter('resource_toolbox_human_readable_filesize', [ToolboxExtensionRuntime::class, 'humanReadableFilesize']),
        ];
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('resource_toolbox_has_bookmarks', [ToolboxExtensionRuntime::class, 'hasBookmarks']),
            new TwigFunction(
                'resource_toolbox_timeline_entry',
                [ToolboxExtensionRuntime::class, 'renderTimelineEntry'],
                ['is_safe' => ['html']],
            ),
            new TwigFunction('resource_toolbox_user_label', [ToolboxExtensionRuntime::class, 'timelineUserLabel']),
            new TwigFunction('resource_toolbox_profile_image', [ToolboxExtensionRuntime::class, 'timelineUserProfileImage']),
            new TwigFunction('resource_toolbox_timeline_diff_rows', [ToolboxExtensionRuntime::class, 'timelineDiffRows']),
            new TwigFunction('resource_toolbox_file_icon', [ToolboxExtensionRuntime::class, 'fileIcon']),
            new TwigFunction('resource_toolbox_icon_color', [ToolboxExtensionRuntime::class, 'iconColor']),
        ];
    }
}
