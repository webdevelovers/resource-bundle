<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\Resolver;

use Symfony\Component\HttpFoundation\Request;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

use function is_array;
use function is_string;

final class IndexStateResolver implements IndexStateResolverInterface
{
    public function resolve(Request $request, IndexDefinitionInterface $definition): IndexState
    {
        $criteria = $request->query->all('criteria');
        $sort = $request->query->all('sort');
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('perPage', $definition->getDefaultPerPage());
        $view = $request->query->getString('view', $definition->getDefaultView());

        return new IndexState(
            criteria: $this->normalizeCriteria($criteria),
            sort: $sort,
            page: $page,
            perPage: $perPage,
            view: $view,
        );
    }

    /**
     * @param array<int, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCriteria(array $criteria): array
    {
        $normalized = [];

        foreach ($criteria as $criterion) {
            if (! is_array($criterion)) {
                continue;
            }

            $field = $criterion['field'] ?? null;
            $operator = $criterion['operator'] ?? null;

            if (! is_string($field) || $field === '') {
                continue;
            }

            if (! is_string($operator) || $operator === '') {
                $operator = 'equals';
            }

            $normalized[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $criterion['value'] ?? null,
            ];
        }

        return $normalized;
    }
}
