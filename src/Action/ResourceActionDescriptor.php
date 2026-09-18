<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Action;

final readonly class ResourceActionDescriptor
{
    /** @param array<string> $methods */
    public function __construct(
        public string $resourceAlias,
        public ResourceActionType $type,
        public string $label,
        public string|null $icon,
        public string|null $buttonClass,
        public int $priority,
        public bool $requiresConfirmation,
        public string|null $template,
        public string $routeName,
        public string $path,
        public array $methods,
        public string|null $confirmMessage = null,
        public string|null $modalId = null,
        public string|null $modalInternalComponent = null,
        /** @var array<string,mixed>|null */
        public array|null $modalContext = null,
    ) {
    }

    public function isGetOnly(): bool
    {
        return $this->methods === ['GET'] || $this->methods === ['GET', 'HEAD'];
    }

    public function opensModal(): bool
    {
        return $this->modalId !== null;
    }
}
