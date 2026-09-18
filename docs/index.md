# Index system

The Index system provides a flexible way to define and execute resource indexes (currently focused on table-like views) with:

- immutable constraints (always-on pre-filters);
- user filters (including predefined filters);
- sorting;
- view switching;
- paginated results.

It is designed to be HTTP-friendly: index state can be represented by request parameters and normalized before querying.

## Architecture overview

Main building blocks:

- `#[AsResourceIndex]` on an index class (metadata: name, resource class, provider, build method).
- `RegisterResourceIndexPass` automatically tags annotated classes as `app.resource.index`.
- `IndexRegistry` collects tagged index services and exposes index definitions by name.
- `ResourceIndexDefinitionFactory` executes your index `buildIndex(...)` method and produces an `IndexDefinition`.
- `IndexStateNormalizer` sanitizes incoming state (allowed filters/sorts/view, page/perPage/offset bounds).
- `IndexDataProviderInterface` executes a definition+state and returns an `IndexResult`.
- `DoctrineOrmIndexDataProvider` is the provided Doctrine ORM implementation.

## Define an index

Create a class in your host app and annotate it with `#[AsResourceIndex]`.

```php
<?php

namespace App\ResourceIndex;

use App\Entity\Product;
use WebDevelovers\ResourceBundle\Attribute\AsResourceIndex;
use WebDevelovers\ResourceBundle\Index\Builder\IndexBuilderInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\BooleanFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\DateRangeFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\EntityFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\TextFilter;
use WebDevelovers\ResourceBundle\Index\Definition\Sort\SortDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\View\TableViewDefinition;

#[AsResourceIndex(
    name: 'wd.product_table',
    resourceClass: Product::class,
    provider: 'app.resource.index.provider.doctrine.orm',
)]
final class ProductTable
{
    public function buildIndex(IndexBuilderInterface $builder): void
    {
        $builder
            ->setDefaultView('table')
            ->setDefaultPerPage(25)

            // Immutable constraints (always applied).
            ->addConstraint('enabled', true, 'equals')

            // User filters.
            ->addFilter(new TextFilter('name', 'Nome'))
            ->addFilter(new TextFilter('sku', 'SKU', field: 'externalReference'))
            ->addFilter(new BooleanFilter('synchronized', 'Sincronizzato'))
            ->addFilter(new DateRangeFilter('createdAt', 'Creato il'))
            ->addFilter(new EntityFilter('categoryId', target: 'App\\Entity\\Category', label: 'Categoria', multiple: true))

            // User sorting.
            ->addSort(new SortDefinition('name', 'name', 'asc'))
            ->addSort(new SortDefinition('createdAt', 'createdAt', 'desc'))

            // Views.
            ->addView(new TableViewDefinition(
                fields: [
                    'name' => ['type' => 'string', 'label' => 'Nome'],
                    'createdAt' => ['type' => 'datetime', 'label' => 'Creato il', 'format' => 'd/m/Y H:i'],
                ],
            ));
    }
}
```

### `AsResourceIndex` options

- `name` (required): unique index id (used by registry/application).
- `resourceClass` (optional but required by Doctrine provider): entity class.
- `provider` (optional): provider service id to use for data retrieval.
- `buildMethod` (default: `buildIndex`): method called by the definition factory.

## Definition model

`IndexDefinition` contains:

- `constraints`: fixed conditions, not user-editable.
- `filters`: user-applicable filters (`array<string, FilterDefinitionInterface>`).
- `sorts`: allowed sorts (`array<string, SortDefinitionInterface>`).
- `views`: available views (`array<string, IndexViewDefinitionInterface>`).
- `defaultView`, `defaultPerPage`.

`IndexBuilder` helps assembling the definition fluently.

## Predefined filters

To reduce repetitive configuration, you can use:

- `TextFilter`
  - type: `text`
  - options: `field` (default = filter name), `case_insensitive` (default `true`)
- `BooleanFilter`
  - type: `boolean`
  - options: `field` (default = filter name)
- `DateRangeFilter`
  - type: `date_range`
  - options: `field` (default = filter name)
- `EntityFilter`
  - type: `entity`
  - options: `field` (default = filter name), `target`, `multiple`, plus custom options

You can still use the generic `FilterDefinition` (or your own implementations of `FilterDefinitionInterface`) for custom filter types.

## State normalization (HTTP-friendly behavior)

`IndexState` represents runtime state:

- `criteria`: list of user criteria (`field`, `operator`, `value`)
- `sort`: map of sort name => direction
- `page`, `perPage`, `view`, `offset`

`IndexStateNormalizer` ensures:

- unknown filter criteria are removed;
- unknown sorts are removed;
- invalid view falls back to `defaultView`;
- `page >= 1`, `perPage >= 1`, `offset >= 0`.

This is the base for stable URL-driven states (same parameters => same normalized state/query intent).

## Doctrine ORM provider behavior

`DoctrineOrmIndexDataProvider`:

- starts from `resourceClass` as root alias `resource`;
- applies constraints first, then user criteria;
- supports relation paths with dot notation (auto `LEFT JOIN`, e.g. `category.name`);
- applies explicit sorts from state;
- when no sort is provided, applies the first configured sort with its default direction;
- paginates with `offset` + `perPage` and returns `IndexResult` with items + total count.

Supported operators:

- Criteria: `equals`, `not_equals`, `contains`, `starts_with`, `ends_with`, `in`, `gte`, `lte`
- Constraints: `equals`, `not_equals`, `in`

Provider-specific notes:

- `boolean` filter values are normalized from common string/int forms (`1/0`, `true/false`, `yes/no`, `si/no`, ...).
- `entity` filter with `multiple=true` maps text-like operators (`contains`, `starts_with`, `ends_with`) to `in`.

## Filter options API

For dynamic filter option lists (e.g. select choices), see:

- `FilterOptionProviderInterface`
- `DoctrineFilterOptionProvider`

This allows building UI filter options from Doctrine entities while keeping index definitions clean.

## Minimal runtime flow

At runtime, a typical flow is:

1. Resolve `IndexDefinition` by name from `IndexRegistry`.
2. Build/parse `IndexState` from HTTP query params.
3. Normalize state via `IndexStateNormalizer`.
4. Resolve the provider (`provider` from definition, or your default strategy).
5. Call `provide(...)` and render the selected view.

## Extension points

You can extend the system by:

- adding custom filter classes implementing `FilterDefinitionInterface`;
- adding custom view definitions extending `IndexViewDefinition`;
- implementing your own `IndexDataProviderInterface` for non-Doctrine backends;
- enriching frontend state persistence (e.g. localStorage) on top of normalized HTTP state.

## Current scope

Implemented and stable now:

- index definition/discovery/registry;
- constraints, filters, sorts, views in the definition model;
- state normalization;
- Doctrine ORM provider and option provider;
- predefined common filters.

Potential next steps (not yet part of this implementation):

- additional view types beyond table;
- first-class frontend integration patterns (e.g. Symfony UX widgets);
- built-in state persistence/navigation helpers (localStorage, previous/next in filtered sets).