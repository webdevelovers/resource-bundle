<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Index\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\Constraint\ConstraintDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

use function count;
use function explode;
use function is_array;
use function is_bool;
use function is_int;
use function is_iterable;
use function is_string;
use function max;
use function mb_strtolower;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function strtoupper;
use function trim;

final readonly class DoctrineOrmIndexDataProvider implements IndexDataProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @param array<string, mixed> $context */
    public function provide(
        IndexDefinitionInterface $definition,
        IndexState $state,
        array $context = [],
    ): IndexResult {
        $resourceClass = $definition->getResourceClass();

        if ($resourceClass === null) {
            throw new LogicException(sprintf(
                'Index "%s" has no configured resource class.',
                $definition->getName(),
            ));
        }

        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->select('resource')
            ->from($resourceClass, 'resource');

        foreach ($definition->getConstraints() as $index => $constraint) {
            $this->applyConstraint($queryBuilder, $constraint, $index);
        }

        $this->applyCriteria($queryBuilder, $definition, $state);

        foreach ($state->sort as $name => $direction) {
            $sortDefinition = $definition->getSorts()[$name] ?? null;

            if ($sortDefinition === null) {
                continue;
            }

            $path = $sortDefinition->getPath() ?? $name;
            $normalizedDirection = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
            $orderByExpression = $this->resolvePathExpression($queryBuilder, (string) $path);

            $queryBuilder->addOrderBy($orderByExpression, $normalizedDirection);
        }

        if ($state->sort === []) {
            foreach ($definition->getSorts() as $name => $sortDefinition) {
                $path = $sortDefinition->getPath() ?? $name;
                $direction = strtoupper($sortDefinition->getDefaultDirection()) === 'DESC' ? 'DESC' : 'ASC';
                $orderByExpression = $this->resolvePathExpression($queryBuilder, (string) $path);

                $queryBuilder->addOrderBy($orderByExpression, $direction);
                break;
            }
        }

        $offset = max(0, $state->offset);

        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($state->perPage);

        $items = $queryBuilder->getQuery()->getResult();

        $countQueryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(resource)')
            ->from($resourceClass, 'resource');

        foreach ($definition->getConstraints() as $index => $constraint) {
            $this->applyConstraint($countQueryBuilder, $constraint, $index);
        }

        $this->applyCriteria($countQueryBuilder, $definition, $state);

        $totalItems = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();

        return new IndexResult(
            items: $items,
            page: intdiv($offset, $state->perPage) + 1,
            perPage: $state->perPage,
            totalItems: $totalItems,
            context: [
                'resource_class' => $resourceClass,
                'index' => $definition->getName(),
                ...$context,
            ],
        );
    }

    private function applyCriteria(
        QueryBuilder $queryBuilder,
        IndexDefinitionInterface $definition,
        IndexState $state,
    ): void {
        foreach ($state->criteria as $index => $criterion) {
            if (! is_array($criterion)) {
                continue;
            }

            $this->applyCriterion($queryBuilder, $definition, $criterion, $index);
        }
    }

    /** @param array<string, mixed> $criterion */
    private function applyCriterion(
        QueryBuilder $queryBuilder,
        IndexDefinitionInterface $definition,
        array $criterion,
        int $index,
    ): void {
        $field = $criterion['field'] ?? null;
        $operator = $criterion['operator'] ?? 'equals';
        $value = $criterion['value'] ?? null;

        if (! is_string($field) || $field === '') {
            return;
        }

        if (! is_string($operator) || $operator === '') {
            $operator = 'equals';
        }

        $filterDefinition = $definition->getFilters()[$field] ?? null;

        if ($filterDefinition === null) {
            return;
        }

        $options = $filterDefinition->getOptions();
        $path = $options['field'] ?? $field;
        $filterType = $filterDefinition->getType();

        if (! is_string($path) || $path === '') {
            $path = $field;
        }

        if ($filterType === 'entity') {
            $normalizedOperator = strtolower($operator);

            if ($normalizedOperator === 'contains' || $normalizedOperator === 'starts_with' || $normalizedOperator === 'ends_with') {
                $operator = ! empty($options['multiple']) ? 'in' : 'equals';
            }
        }

        if ($filterType === 'boolean') {
            $normalizedOperator = strtolower($operator);

            if (
                $normalizedOperator !== 'equals'
                && $normalizedOperator !== 'not_equals'
            ) {
                $operator = 'equals';
            }

            $normalizedBooleanValue = $this->normalizeBooleanValue($value);

            if ($normalizedBooleanValue === null) {
                return;
            }

            $value = $normalizedBooleanValue;
        }

        $caseInsensitive = isset($options['case_insensitive']) ? (bool) $options['case_insensitive'] : false;

        $parameterName = sprintf('criterion_%s_%d', str_replace('.', '_', $field), $index);
        $expressionPath = $this->resolvePathExpression($queryBuilder, $path);
        $fieldExpression = $this->normalizeExpression($expressionPath, $caseInsensitive);
        $parameterValue = $this->normalizeValue($value, $caseInsensitive);

        match (strtolower($operator)) {
            'equals' => $queryBuilder
                ->andWhere(sprintf('%s = :%s', $fieldExpression, $parameterName))
                ->setParameter($parameterName, $parameterValue),
            'not_equals' => $queryBuilder
                ->andWhere(sprintf('%s != :%s', $fieldExpression, $parameterName))
                ->setParameter($parameterName, $parameterValue),
            'contains' => $queryBuilder
                ->andWhere(sprintf('%s LIKE :%s', $fieldExpression, $parameterName))
                ->setParameter($parameterName, '%' . (string) $parameterValue . '%'),
            'starts_with' => $queryBuilder
                ->andWhere(sprintf('%s LIKE :%s', $fieldExpression, $parameterName))
                ->setParameter($parameterName, (string) $parameterValue . '%'),
            'ends_with' => $queryBuilder
                ->andWhere(sprintf('%s LIKE :%s', $fieldExpression, $parameterName))
                ->setParameter($parameterName, '%' . (string) $parameterValue),
            'in' => $this->applyInCriterion($queryBuilder, $expressionPath, $parameterName, $value),
            'gte' => $queryBuilder
                ->andWhere(sprintf('%s >= :%s', $expressionPath, $parameterName))
                ->setParameter($parameterName, $value),
            'lte' => $queryBuilder
                ->andWhere(sprintf('%s <= :%s', $expressionPath, $parameterName))
                ->setParameter($parameterName, $value),
            default => throw new LogicException(sprintf(
                'Unsupported criterion operator "%s" for field "%s".',
                $operator,
                $field,
            )),
        };
    }

    private function normalizeBooleanValue(mixed $value): bool|null
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 't', 'yes', 'y', 'si', 's' => true,
            '0', 'false', 'f', 'no', 'n' => false,
            default => null,
        };
    }

    private function applyConstraint(
        QueryBuilder $queryBuilder,
        ConstraintDefinitionInterface $constraint,
        int $index,
    ): void {
        $field = $constraint->getField();
        $operator = strtolower($constraint->getOperator());
        $parameterName = sprintf('constraint_%s_%d', str_replace('.', '_', $field), $index);
        $path = $this->resolvePathExpression($queryBuilder, $field);

        match ($operator) {
            'equals' => $queryBuilder
                ->andWhere(sprintf('%s = :%s', $path, $parameterName))
                ->setParameter($parameterName, $constraint->getValue()),
            'not_equals' => $queryBuilder
                ->andWhere(sprintf('%s != :%s', $path, $parameterName))
                ->setParameter($parameterName, $constraint->getValue()),
            'in' => $this->applyInConstraint($queryBuilder, $path, $parameterName, $constraint->getValue()),
            default => throw new LogicException(sprintf(
                'Unsupported constraint operator "%s" for field "%s".',
                $constraint->getOperator(),
                $field,
            )),
        };
    }

    private function resolvePathExpression(QueryBuilder $queryBuilder, string $path): string
    {
        if (! str_contains($path, '.')) {
            return sprintf('resource.%s', $path);
        }

        $parts = explode('.', $path);
        $currentAlias = 'resource';
        $lastIndex = count($parts) - 1;

        foreach ($parts as $index => $part) {
            if ($index === $lastIndex) {
                return sprintf('%s.%s', $currentAlias, $part);
            }

            $join = sprintf('%s.%s', $currentAlias, $part);
            $joinAlias = sprintf('%s__%s', $currentAlias, $part);
            $this->ensureLeftJoin($queryBuilder, $currentAlias, $join, $joinAlias);

            $currentAlias = $joinAlias;
        }

        return sprintf('resource.%s', $path);
    }

    private function ensureLeftJoin(
        QueryBuilder $queryBuilder,
        string $fromAlias,
        string $join,
        string $joinAlias,
    ): void {
        $joins = $queryBuilder->getDQLPart('join');
        $fromJoins = $joins[$fromAlias] ?? [];

        foreach ($fromJoins as $existingJoin) {
            if (! $existingJoin instanceof Join) {
                continue;
            }

            if ($existingJoin->getJoin() === $join || $existingJoin->getAlias() === $joinAlias) {
                return;
            }
        }

        $queryBuilder->leftJoin($join, $joinAlias);
    }

    private function normalizeExpression(string $expression, bool $caseInsensitive): string
    {
        if (! $caseInsensitive) {
            return $expression;
        }

        return sprintf('LOWER(%s)', $expression);
    }

    private function normalizeValue(mixed $value, bool $caseInsensitive): mixed
    {
        if (! $caseInsensitive || ! is_string($value)) {
            return $value;
        }

        return mb_strtolower($value);
    }

    private function applyInConstraint(
        QueryBuilder $queryBuilder,
        string $path,
        string $parameterName,
        mixed $value,
    ): void {
        if (! is_iterable($value)) {
            throw new LogicException(sprintf(
                'Constraint operator "in" expects an iterable value for "%s".',
                $path,
            ));
        }

        $queryBuilder
            ->andWhere(sprintf('%s IN (:%s)', $path, $parameterName))
            ->setParameter($parameterName, $value);
    }

    private function applyInCriterion(
        QueryBuilder $queryBuilder,
        string $path,
        string $parameterName,
        mixed $value,
    ): void {
        if (! is_iterable($value)) {
            throw new LogicException(sprintf(
                'Criterion operator "in" expects an iterable value for "%s".',
                $path,
            ));
        }

        $queryBuilder
            ->andWhere(sprintf('%s IN (:%s)', $path, $parameterName))
            ->setParameter($parameterName, $value);
    }
}
