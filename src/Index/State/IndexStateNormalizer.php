<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\State;

use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;

use function array_filter;
use function array_values;
use function is_string;
use function max;

use const ARRAY_FILTER_USE_KEY;

final class IndexStateNormalizer implements IndexStateNormalizerInterface
{
    public function normalize(IndexState $state, IndexDefinitionInterface $definition): IndexState
    {
        $view = $definition->hasView($state->view) ? $state->view : $definition->getDefaultView();
        $page = max(1, $state->page);
        $perPage = max(1, $state->perPage);
        $offset = max(0, $state->offset);

        $allowedFilters = $definition->getFilters();
        $criteria = array_values(array_filter(
            $state->criteria,
            static function (array $criterion) use ($allowedFilters): bool {
                $field = $criterion['field'] ?? null;

                return is_string($field) && isset($allowedFilters[$field]);
            },
        ));

        $allowedSorts = $definition->getSorts();
        $sort = array_filter(
            $state->sort,
            static fn (string $name): bool => isset($allowedSorts[$name]),
            ARRAY_FILTER_USE_KEY,
        );

        return new IndexState(
            criteria: $criteria,
            sort: $sort,
            page: $page,
            perPage: $perPage,
            view: $view,
            offset: $offset,
        );
    }
}
