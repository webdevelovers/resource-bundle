<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\State;

final readonly class IndexState
{
    /**
     * @param array<int, array<string, mixed>> $criteria
     * @param array<string, string> $sort
     */
    public function __construct(
        public array $criteria = [],
        public array $sort = [],
        public int $page = 1,
        public int $perPage = 25,
        public string $view = 'table',
        public int $offset = 0,
    ) {
    }
}
