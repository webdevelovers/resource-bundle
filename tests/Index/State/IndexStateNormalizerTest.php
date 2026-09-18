<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Index\State;

use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\FilterDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\Sort\SortDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinition;
use WebDevelovers\ResourceBundle\Index\State\IndexState;
use WebDevelovers\ResourceBundle\Index\State\IndexStateNormalizer;

final class IndexStateNormalizerTest extends TestCase
{
    public function testNormalizeKeepsOnlyAllowedFiltersAndSortAndNormalizesBounds(): void
    {
        $definition = new IndexDefinition(
            name: 'wd.product_index',
            defaultView: 'table',
            defaultPerPage: 25,
            filters: [
                'name' => new FilterDefinition('name', 'text'),
                'synchronized' => new FilterDefinition('synchronized', 'boolean'),
            ],
            sorts: [
                'name' => new SortDefinition('name', 'name', 'asc'),
            ],
            views: [
                'table' => new IndexViewDefinition('table', 'table'),
            ],
        );

        $state = new IndexState(
            criteria: [
                ['field' => 'name', 'operator' => 'contains', 'value' => 'abc'],
                ['field' => 'unknown', 'operator' => '=', 'value' => 123],
                ['operator' => '=', 'value' => 'missing_field'],
            ],
            sort: [
                'name' => 'desc',
                'invalid' => 'asc',
            ],
            page: -5,
            perPage: 0,
            view: 'cards',
            offset: -100,
        );

        $normalizer = new IndexStateNormalizer();
        $normalized = $normalizer->normalize($state, $definition);

        self::assertSame([
            ['field' => 'name', 'operator' => 'contains', 'value' => 'abc'],
        ], $normalized->criteria);
        self::assertSame(['name' => 'desc'], $normalized->sort);
        self::assertSame(1, $normalized->page);
        self::assertSame(1, $normalized->perPage);
        self::assertSame('table', $normalized->view);
        self::assertSame(0, $normalized->offset);
    }
}
