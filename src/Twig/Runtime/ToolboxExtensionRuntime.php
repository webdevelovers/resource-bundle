<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Runtime;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;
use WebDevelovers\ResourceBundle\Security\CurrentUserProviderInterface;
use WebDevelovers\ResourceBundle\Security\DoctrineUserClassResolver;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Attachment;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\TimelineDiffPresenter;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManager;
use function count;
use function explode;
use function is_string;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function trim;

readonly class ToolboxExtensionRuntime implements RuntimeExtensionInterface
{
    public const string MEGABYTES = 'MB';

    public function __construct(
        private ToolboxManager $toolboxManager,
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private TimelineDiffPresenter $timelineDiffPresenter,
        private readonly CurrentUserProviderInterface $currentUserProvider,
        private DoctrineUserClassResolver $userClassResolver,
    ) {
    }

    public function hasBookmarks(): bool
    {
        $user = $this->currentUserProvider->getUser();
        if($user === null) {
            return false;
        }

        return count($this->toolboxManager->getBookmarks($user)) > 0;
    }

    public function renderTimelineEntry(TimelineEntry $entry, string $resourceType): string
    {
        $template = $this->resolveTimelineTemplate($entry->event);

        return $this->twig->render($template, [
            'entry' => $entry,
            'resourceType' => $resourceType,
        ]);
    }

    public function timelineUserLabel(mixed $blameId): string
    {
        $id = $this->normalizeId($blameId);
        if ($id === null) {
            return 'Sistema';
        }

        $userClass = $this->userClassResolver->resolve();
        $user = $this->entityManager->getRepository($userClass)->find($id);
        if (! $user instanceof UserInterface) {
            return 'Sistema';
        }

        $fullName = trim((string) ($user->fullName ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $firstName = trim((string) ($user->firstName ?? ''));
        $lastName = trim((string) ($user->lastName ?? ''));

        if ($firstName !== '' && $lastName !== '') {
            return sprintf('%s %s', $firstName, $lastName);
        }

        if ($firstName !== '') {
            return $firstName;
        }

        if ($lastName !== '') {
            return $lastName;
        }

        return (string) ($user->username ?? 'Utente');
    }

    public function timelineUserProfileImage(mixed $blameId): string|null
    {
        $id = $this->normalizeId($blameId);
        if ($id === null) {
            return null;
        }

        $userClass = $this->userClassResolver->resolve();
        $user = $this->entityManager->getRepository($userClass)->find($id);
        if (! $user instanceof UserInterface) {
            return null;
        }

        // Placeholder: quando avrai un campo/avatar file, ritorna qui l'URL.
        return null;
    }

    /** @return array<int, array{field: string, label: string, old: string, new: string}> */
    public function timelineDiffRows(TimelineEntry $entry): array
    {
        return $this->timelineDiffPresenter->buildRows($entry);
    }

    public function humanReadableFilesize(int $size, string $unit = self::MEGABYTES): string
    {
        if ((! $unit && $size >= 1 << 30) || $unit === 'GB') {
            return number_format($size / (1 << 30), 2) . $unit;
        }

        if ((! $unit && $size >= 1 << 20) || $unit === 'MB') {
            return number_format($size / (1 << 20), 2) . $unit;
        }

        if ((! $unit && $size >= 1 << 10) || $unit === 'KB') {
            return number_format($size / (1 << 10), 2) . $unit;
        }

        return number_format($size) . ' bytes';
    }

    public function fileIcon(Attachment $attachment): string
    {
        $fromExtension = $this->matchFileExtension($attachment->originalName);
        if ($fromExtension !== null) {
            return $fromExtension;
        }

        $fromFilename = $this->matchFileExtension($attachment->fileName);
        if ($fromFilename !== null) {
            return $fromFilename;
        }

        $fromMimeType = $this->matchMimeType($attachment->mimeType);
        if ($fromMimeType !== null) {
            return $fromMimeType;
        }

        return 'bi:file-earmark';
    }

    public function iconColor(Attachment $attachment): string
    {
        return $this->matchColor($this->fileIcon($attachment));
    }


    private function normalizeId(mixed $value): string|null
    {
        if ($value instanceof Uuid) {
            return $value->toRfc4122();
        }

        if (is_string($value) && Uuid::isValid($value)) {
            return $value;
        }

        return null;
    }

    private function resolveTimelineTemplate(string $event): string
    {
        $normalized = str_replace('.', '/', $event);

        $candidates = [
            sprintf('@WebDeveloversResource/toolbox/timeline/%s.html.twig', $normalized), // audit/create.html.twig
            sprintf('@WebDeveloversResource/toolbox/timeline/%s.html.twig', $event),      // note.html.twig
            '@WebDeveloversResource/toolbox/timeline/base_event.html.twig',
        ];

        if (str_starts_with($event, 'audit.')) {
            $parts = explode('.', $event);
            $action = $parts[1] ?? null;

            if ($action !== null) {
                $candidates[] = sprintf('@WebDeveloversResource/toolbox/timeline/audit/%s.html.twig', $action); // nuova struttura
                $candidates[] = sprintf('@WebDeveloversResource/toolbox/timeline/%s.html.twig', $action);       // fallback legacy
            }

            $candidates[] = '@WebDeveloversResource/toolbox/timeline/audit/default.html.twig';
        }

        $candidates[] = '@WebDeveloversResource/toolbox/timeline/base.html.twig';

        foreach ($candidates as $candidate) {
            if ($this->twig->getLoader()->exists($candidate)) {
                return $candidate;
            }
        }

        return '@WebDeveloversResource/toolbox/timeline/base.html.twig';
    }

    private function matchColor(string $icon): string
    {
        return match ($icon) {
            'bi:file-earmark-word',
            'bi:file-earmark-excel',
            'bi:file-earmark-ppt',
            'bi:file-earmark-text' => '#07A0C3',
            'bi:file-earmark-zip' => '#EFD6AC',
            'bi:file-earmark-image' => '#198754',
            'bi:file-earmark-pdf' => '#dc3545',
            default => '#4D4D4D'
        };
    }

    private function matchMimeType(string|null $mimeType): string|null
    {
        if ($mimeType === null) {
            return null;
        }

        return match ($mimeType) {
            // Documenti della suite office
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi:file-earmark-word',

            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv' => 'bi:file-earmark-excel',

            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'bi:file-earmark-ppt',

            'text/plain',
            'text/markdown' => 'bi:file-earmark-text',

            // File immagine
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/tiff',
            'image/webp',
            'image/svg+xml' => 'bi:file-earmark-image',

            // File archivio
            'application/zip',
            'application/x-rar-compressed',
            'application/x-tar',
            'application/x-7z-compressed',
            'application/gzip' => 'bi:file-earmark-zip',

            'application/pdf' => 'bi:file-earmark-pdf',

            default => null,
        };
    }

    private function matchFileExtension(string|null $filename): string|null
    {
        if ($filename === null) {
            return null;
        }

        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (empty($fileExtension)) {
            return null;
        }

        return match ($fileExtension) {
            'doc',
            'docx' => 'bi:file-earmark-word',

            'xls',
            'xlsx',
            'csv' => 'bi:file-earmark-excel',

            'ppt',
            'pptx' => 'bi:file-earmark-ppt',

            'txt',
            'md' => 'bi:file-earmark-text',

            'jpg',
            'jpeg',
            'png',
            'gif',
            'bmp',
            'tiff',
            'webp',
            'svg' => 'bi:file-earmark-image',

            'zip',
            'rar',
            'tar',
            '7z',
            'gz' => 'bi:file-earmark-zip',

            'pdf' => 'bi:file-earmark-pdf',
            default => null,
        };
    }
}
