<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Attachment;

use function number_format;
use function pathinfo;
use function strtolower;

use const PATHINFO_EXTENSION;

class AttachmentExtensionRuntime implements RuntimeExtensionInterface
{
    public const string MEGABYTES = 'MB';

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
        $fromExtension = $this->matchFileExtension($attachment->getOriginalName());
        if ($fromExtension !== null) {
            return $fromExtension;
        }

        $fromFilename = $this->matchFileExtension($attachment->getFileName());
        if ($fromFilename !== null) {
            return $fromFilename;
        }

        $fromMimeType = $this->matchMimeType($attachment->getMimeType());
        if ($fromMimeType !== null) {
            return $fromMimeType;
        }

        return 'bi:file-earmark';
    }

    public function iconColor(Attachment $attachment): string
    {
        return $this->matchColor($this->fileIcon($attachment));
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
