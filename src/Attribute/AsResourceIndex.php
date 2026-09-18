<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsResourceIndex
{
    public function __construct(
        public string $name,
        public string|null $resourceClass = null,
        public string|null $provider = null,
        public string $buildMethod = 'buildIndex',
    ) {
    }
}
