<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\DataProvider\Option;

use Doctrine\ORM\EntityManagerInterface;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;

use function array_map;
use function array_values;
use function in_array;
use function is_array;
use function is_string;
use function max;
use function mb_strtolower;
use function method_exists;
use function property_exists;
use function sprintf;
use function str_contains;
use function ucfirst;

final readonly class DoctrineFilterOptionProvider implements FilterOptionProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string> $selected
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function getOptions(
        IndexDefinitionInterface $definition,
        string $filterName,
        string $query = '',
        array $selected = [],
    ): array {
        $filter = $definition->getFilters()[$filterName] ?? null;

        if ($filter === null) {
            return [];
        }

        $type = $filter->getType();
        $options = $filter->getOptions();

        return match ($type) {
            'choice' => $this->getChoiceOptions($options, $query, $selected),
            'entity' => $this->getEntityOptions($options, $query, $selected),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string> $selected
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function getChoiceOptions(array $options, string $query, array $selected): array
    {
        $choices = $options['choices'] ?? [];

        if (! is_array($choices)) {
            return [];
        }

        $query = mb_strtolower($query);

        $result = [];

        foreach ($choices as $value => $label) {
            $value = (string) $value;
            $label = (string) $label;

            if (
                $query !== '' &&
                ! str_contains(mb_strtolower($label), $query) &&
                ! str_contains(mb_strtolower($value), $query)
            ) {
                continue;
            }

            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        foreach ($selected as $value) {
            $value = (string) $value;
            if (in_array($value, array_map(static fn (array $item): string => $item['value'], $result), true)) {
                continue;
            }

            if (! isset($choices[$value])) {
                continue;
            }

            $result[] = [
                'value' => $value,
                'label' => (string) $choices[$value],
            ];
        }

        return array_values($result);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string> $selected
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function getEntityOptions(array $options, string $query, array $selected): array
    {
        $target = $options['target'] ?? null;

        if (! is_string($target) || $target === '') {
            return [];
        }

        $searchProperty = $options['search_property'] ?? null;
        $labelProperty = $options['label_property'] ?? null;
        $limit = isset($options['limit']) ? max(1, (int) $options['limit']) : 20;
        $constraints = $options['constraints'] ?? [];

        $repository = $this->entityManager->getRepository($target);

        $qb = $repository->createQueryBuilder('entity');

        if (is_array($constraints)) {
            foreach ($constraints as $field => $constraintValue) {
                if (! is_string($field) || $field === '') {
                    continue;
                }

                $parameterName = 'constraint_' . $field;

                $qb
                    ->andWhere(sprintf('entity.%s = :%s', $field, $parameterName))
                    ->setParameter($parameterName, $constraintValue);
            }
        }

        if (is_string($searchProperty) && $searchProperty !== '' && $query !== '') {
            $qb
                ->andWhere(sprintf('LOWER(entity.%s) LIKE :q', $searchProperty))
                ->setParameter('q', '%' . mb_strtolower($query) . '%');
        }

        $qb->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        $result = [];

        foreach ($items as $item) {
            $value = method_exists($item, 'getId') ? (string) $item->getId() : (string) ($item->id ?? '');
            if ($value === '') {
                continue;
            }

            $label = $this->resolveLabel($item, $labelProperty);

            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return array_values($result);
    }

    private function resolveLabel(object $item, mixed $labelProperty): string
    {
        if (is_string($labelProperty) && $labelProperty !== '') {
            $getter = 'get' . ucfirst($labelProperty);
            if (method_exists($item, $getter)) {
                return (string) $item->{$getter}();
            }

            if (property_exists($item, $labelProperty)) {
                return (string) $item->{$labelProperty};
            }
        }

        if (method_exists($item, '__toString')) {
            return (string) $item;
        }

        return (string) (method_exists($item, 'getId') ? $item->getId() : '');
    }
}
