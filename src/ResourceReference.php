<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle;

readonly class ResourceReference
{
    public function __construct(
        public string $subjectId,
        public string $subjectName,
        public string $resourceAlias,
    ) {
    }
}
