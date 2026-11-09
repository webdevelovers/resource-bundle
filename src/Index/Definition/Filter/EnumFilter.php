<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Definition\Filter;

/**
 * Filter definition for PHP backed enums: it is rendered as a select of
 * "readable" labels (translated when the enum implements Symfony's
 * `TranslatableInterface`, falling back to a `label()` method and finally to
 * the raw case value), while the underlying search criterion keeps using the
 * real scalar enum value (`BackedEnum::value`).
 */
final readonly class EnumFilter implements FilterDefinitionInterface
{
    /** @param class-string $enumClass */
    public function __construct(
        private string $name,
        private string $enumClass,
        private string|null $label = null,
        private string|null $field = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return 'enum';
    }

    public function getLabel(): string|null
    {
        return $this->label;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return [
            'field' => $this->field ?? $this->name,
            'enum_class' => $this->enumClass,
        ];
    }
}
