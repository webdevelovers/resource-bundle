<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\DataProvider\Result;

final readonly class IndexResult
{
    /**
     * @param array $items
     * @param array<string, mixed> $context
     */
    public function __construct(
        public iterable $items,
        public int $page,
        public int $perPage,
        public int $totalItems,
        public array $context = [],
    ) {
    }
}
