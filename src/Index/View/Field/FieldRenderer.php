<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\View\Field;

use BackedEnum;
use DateTimeInterface;
use Stringable;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use UnitEnum;

use function is_bool;
use function is_scalar;
use function is_string;

final readonly class FieldRenderer implements FieldRendererInterface
{
    public function __construct(
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $context
     */
    public function render(
        object $resource,
        string $name,
        array $field,
        mixed $value,
        array $context = [],
    ): string {
        if (isset($field['template']) && is_string($field['template'])) {
            return $this->twig->render($field['template'], [
                'resource' => $resource,
                'name' => $name,
                'field' => $field,
                'value' => $value,
                'context' => $context,
            ]);
        }

        $type = $field['type'] ?? 'string';
        $emptyValue = (string) ($field['empty_value'] ?? '—');

        if ($value === null) {
            return $emptyValue;
        }

        return match ($type) {
            'datetime' => $this->renderDateTime($value, $field, 'd/m/Y H:i', $emptyValue),
            'date' => $this->renderDateTime($value, $field, 'd/m/Y', $emptyValue),
            'boolean' => $this->renderBoolean($value, $field),
            'enum' => $this->renderEnum($value, $field, $emptyValue),
            default => $this->renderString($value, $field, $emptyValue),
        };
    }

    /** @param array<string, mixed> $field */
    private function renderString(mixed $value, array $field, string $emptyValue): string
    {
        if ($value === null) {
            return $emptyValue;
        }

        $stringValue = null;

        if ($value instanceof Stringable) {
            $stringValue = (string) $value;
        } elseif (is_scalar($value)) {
            $stringValue = (string) $value;
        }

        if ($stringValue === null || $stringValue === '') {
            return $emptyValue;
        }

        $shouldTranslate = (bool) ($field['translate'] ?? false);
        if (! $shouldTranslate) {
            return $stringValue;
        }

        $prefix = (string) ($field['translation_prefix'] ?? '');

        return $this->translator->trans($prefix . $stringValue);
    }

    /** @param array<string, mixed> $field */
    private function renderDateTime(
        mixed $value,
        array $field,
        string $defaultFormat,
        string $emptyValue,
    ): string {
        if (! $value instanceof DateTimeInterface) {
            return $emptyValue;
        }

        $format = is_string($field['format'] ?? null) ? $field['format'] : $defaultFormat;

        return $value->format($format);
    }

    /** @param array<string, mixed> $field */
    private function renderBoolean(mixed $value, array $field): string
    {
        if (! is_bool($value)) {
            return (string) ($field['empty_value'] ?? '—');
        }

        $trueLabel = (string) ($field['true_label'] ?? 'Sì');
        $falseLabel = (string) ($field['false_label'] ?? 'No');

        return $value ? $trueLabel : $falseLabel;
    }

    /** @param array<string, mixed> $field */
    private function renderEnum(mixed $value, array $field, string $emptyValue): string
    {
        if ($value instanceof BackedEnum) {
            $format = $field['enum_format'] ?? 'value';

            if ($format === 'translation') {
                $prefix = (string) ($field['translation_prefix'] ?? '');

                return $this->translator->trans($prefix . $value->value);
            }

            if ($format === 'name') {
                return $value->name;
            }

            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            $format = $field['enum_format'] ?? 'name';

            if ($format === 'translation') {
                $prefix = (string) ($field['translation_prefix'] ?? '');

                return $this->translator->trans($prefix . $value->name);
            }

            return $value->name;
        }

        return $emptyValue;
    }
}
