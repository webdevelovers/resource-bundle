<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\View\Field;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Throwable;

final class FieldValueResolver
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /** @param array<string, mixed> $field */
    public function resolve(object $resource, string $name, array $field): mixed
    {
        $path = $field['path'] ?? $name;

        try {
            return $this->propertyAccessor->getValue($resource, $path);
        } catch (Throwable) {
            return null;
        }
    }
}
