<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Index\Definition\Filter;

use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\BooleanFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\DateRangeFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\EntityFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\TextFilter;

final class PredefinedFilterTest extends TestCase
{
    public function testTextFilterBuildsExpectedDefinition(): void
    {
        $filter = new TextFilter(
            name: 'name',
            label: 'Nome',
            field: 'productName',
        );

        self::assertSame('name', $filter->getName());
        self::assertSame('text', $filter->getType());
        self::assertSame('Nome', $filter->getLabel());
        self::assertSame([
            'field' => 'productName',
            'case_insensitive' => true,
        ], $filter->getOptions());
    }

    public function testBooleanAndDateRangeFiltersDefaultFieldToName(): void
    {
        $booleanFilter = new BooleanFilter('synchronized', 'Sincronizzato');
        $dateRangeFilter = new DateRangeFilter('createdAt', 'Creato il');

        self::assertSame('boolean', $booleanFilter->getType());
        self::assertSame([
            'field' => 'synchronized',
        ], $booleanFilter->getOptions());

        self::assertSame('date_range', $dateRangeFilter->getType());
        self::assertSame([
            'field' => 'createdAt',
        ], $dateRangeFilter->getOptions());
    }

    public function testEntityFilterMergesOptionsWithDefaults(): void
    {
        $filter = new EntityFilter(
            name: 'category',
            target: DummyCategory::class,
            label: 'Categoria',
            field: 'category',
            multiple: true,
            options: [
                'search_property' => 'name',
                'label_property' => 'name',
            ],
        );

        self::assertSame('entity', $filter->getType());
        self::assertSame([
            'field' => 'category',
            'target' => DummyCategory::class,
            'multiple' => true,
            'search_property' => 'name',
            'label_property' => 'name',
        ], $filter->getOptions());
    }
}

final class DummyCategory
{
}
