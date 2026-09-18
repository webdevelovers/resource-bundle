<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Index\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Index\DataProvider\DoctrineOrmIndexDataProvider;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\BooleanFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\DateRangeFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\EntityFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\TextFilter;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinition;
use WebDevelovers\ResourceBundle\Index\State\IndexState;

final class DoctrineOrmIndexDataProviderTest extends TestCase
{
    public function testProvideAppliesCommonPredefinedFilters(): void
    {
        $mainQuery = $this->createMock(Query::class);
        $mainQuery
            ->expects(self::once())
            ->method('getResult')
            ->willReturn([(object) ['name' => 'Prodotto Alpha']]);

        $countQuery = $this->createMock(Query::class);
        $countQuery
            ->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn('1');

        $mainQueryBuilder = $this->createQueryBuilderMock($mainQuery);
        $countQueryBuilder = $this->createQueryBuilderMock($countQuery);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($mainQueryBuilder, $countQueryBuilder);

        $definition = new IndexDefinition(
            name: 'wd.product_index',
            resourceClass: DummyProduct::class,
            filters: [
                'name' => new TextFilter('name', field: 'name'),
                'synchronized' => new BooleanFilter('synchronized', field: 'synchronized'),
                'createdAt' => new DateRangeFilter('createdAt', field: 'createdAt'),
                'categoryId' => new EntityFilter('categoryId', target: DummyCategory::class, field: 'categoryId', multiple: true),
            ],
            views: [
                'table' => new IndexViewDefinition('table', 'table'),
            ],
        );

        $state = new IndexState(
            criteria: [
                ['field' => 'name', 'operator' => 'contains', 'value' => 'prod'],
                ['field' => 'synchronized', 'operator' => 'equals', 'value' => '1'],
                ['field' => 'createdAt', 'operator' => 'gte', 'value' => new \DateTimeImmutable('2025-01-01 00:00:00')],
                ['field' => 'categoryId', 'operator' => 'contains', 'value' => [1]],
            ],
            perPage: 25,
        );

        $provider = new DoctrineOrmIndexDataProvider($entityManager);
        $result = $provider->provide($definition, $state);

        self::assertSame(1, $result->totalItems);
        self::assertCount(1, $result->items);
        self::assertSame('Prodotto Alpha', $result->items[0]->name);
    }

    private function createQueryBuilderMock(Query $query): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();
        $queryBuilder->method('getRootAliases')->willReturn(['resource']);
        $queryBuilder->method('getDQLPart')->with('join')->willReturn([]);
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }
}

final class DummyProduct
{
}

final class DummyCategory
{
}
