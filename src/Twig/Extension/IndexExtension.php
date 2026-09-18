<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WebDevelovers\ResourceBundle\Index\View\Field\FieldRendererInterface;
use WebDevelovers\ResourceBundle\Index\View\Field\FieldValueResolver;

final class IndexExtension extends AbstractExtension
{
    public function __construct(
        private readonly FieldValueResolver $fieldValueResolver,
        private readonly FieldRendererInterface $fieldRenderer,
    ) {
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('resource_index_render_field', [$this, 'renderField'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $context
     */
    public function renderField(
        object $resource,
        string $name,
        array $field,
        array $context = [],
    ): string {
        $value = $this->fieldValueResolver->resolve($resource, $name, $field);

        return $this->fieldRenderer->render($resource, $name, $field, $value, $context);
    }
}
