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
- `EnumFilter`
  - type: `enum`
  - options: `field` (default = filter name), `enum_class`
  - built for PHP backed enums: the search UI renders a select of "readable" labels, while the
    underlying criterion keeps the real scalar `BackedEnum::value`. Labels are resolved by
    `DoctrineFilterOptionProvider` in this order: (1) if the enum case implements Symfony's
    `Symfony\Contracts\Translation\TranslatableInterface`, its `trans(TranslatorInterface $translator, ?string $locale = null): string`
    method is called (same contract Symfony forms use for enum choices); (2) else, if the enum
    declares a `label(): string` method, it is used as-is (not translated); (3) else, the raw
    `BackedEnum::value` is used as the label.

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
- `enum`/`choice` filters always force the operator to `equals` (or `not_equals`/`in` if explicitly set) and never apply `LOWER()`/`LIKE`, so an enum-backed column mapped to any Doctrine type (`string`, `integer`, ...) is compared with a plain `=`, avoiding the same kind of "function does not exist" errors fixed for `TextFilter` on non-string columns.

## Filter options API

For dynamic filter option lists (e.g. select choices), see:

- `FilterOptionProviderInterface`
- `DoctrineFilterOptionProvider`: besides `choice` (static `value => label` array) and `entity`
  (Doctrine query), it now also resolves `enum` filters by calling `BackedEnum::cases()` and
  translating/labeling each case as described above; the `IndexSearch` live component uses this
  same provider (via `getDraftOptions()`) to render the enum select in the mega-menu, and reuses
  it again to resolve the human-readable `display_value` shown on the active-filter chip.

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

## Search

The search system is the user-facing counterpart of the filtering model already described above
(`filters`, `IndexState#criteria`, `IndexStateNormalizer`). It is designed as a **loosely coupled
layer** on top of the index definition: the index class only declares *what can be searched*
(filters), while a dedicated set of classes/components own *how the user searches* (UI, URL
binding, presets, saved filters, navigation state). This keeps `buildIndex()` focused on data
access concerns, and lets the search UI evolve independently.

### Goals

- Compact, single-input UI inspired by [VisualSearch](https://documentcloud.github.io/visualsearch/)
  and Odoo's search bar: one input/div, clicking it opens a "mega-menu" below it with the
  available filters grouped in columns.
- Three kinds of filters, all expressed on top of the existing filter definitions:
  - **simple filters**: a plain `field` + `operator` + `value` triple, backed 1:1 by a
    `FilterDefinitionInterface` entry of the index (e.g. `name contains "foo"`);
  - **preset filters**: a named, pre-composed combination of one or more simple filters (and
    optionally sort/view), defined on the table/index side (e.g. "My orders" = `owner equals
    current_user`). Clicking a preset is expected to apply it immediately;
  - **saved filters**: user-defined combinations of simple/preset filters, persisted somewhere
    (storage backend TBD) so that they can be reused later. Clicking a saved filter is expected to
    apply it immediately, like a preset.
- Active filters are rendered as independent "field: value" chips/tokens inside the single search
  input/div (same interaction pattern as VisualSearch/Odoo).
- Each active filter chip can be **removed** or **edited** (re-opens the corresponding
  filter editor, prefilled with its current value) without affecting the other active filters.
- Choosing a filter (simple, preset or saved) from the mega-menu, or confirming an edit, **closes**
  the mega-menu and updates the active filters.
- The search state is **URL-driven**: applying/removing/editing a filter updates the page URL, and
  loading a URL with search parameters deterministically reconstructs the same search state
  (same parameters => same `IndexState`, via `IndexStateNormalizer`).
- The search state must be **preserved during navigation**: opening a record from a search result
  and going back (or navigating previous/next record) must not lose the search in progress.

### Relationship with the index definition

Search is declared **inside the same class used to define the index** (the one annotated with
`#[AsResourceIndex]`), reusing `IndexBuilderInterface`:

- simple filters: the existing `addFilter()` / `addFilters()` methods (`TextFilter`,
  `BooleanFilter`, `DateRangeFilter`, `EntityFilter`, or any `FilterDefinitionInterface`);
- presets: declared next to filters as named shortcuts built from the index's own simple filters
  (e.g. a `PresetFilter` holding a label and a list of criteria/sort to apply). They live in the
  definition model (`IndexDefinition`) alongside `filters`, `sorts`, `views`, so they can be
  resolved by name and validated by `IndexStateNormalizer` exactly like any other filter;
- saved filters: **not** declared in the index class (they are user data, not table metadata).
  The index class only needs to expose which simple/preset filters exist; the storage of saved
  combinations (DB table, user preference, etc.) is an implementation detail to be decided
  separately, and is only a consumer of the index's `filters`/`presets`.

This keeps the controlling classes (search component, Stimulus controller, storage) sufficiently
delimited: they depend on the index definition's *public contract* (`filters`, `presets`, `sorts`,
`views`), never on provider/query internals.

### UI building blocks (Symfony UX / Live Components)

The UI is implemented with **Symfony UX Live Components**, with a dedicated Stimulus controller
only for the interactions that are not naturally expressed as Live Component actions (e.g.
opening/closing the mega-menu, keyboard navigation inside the input). Plain inline JS is avoided
in favor of the Stimulus controller.

Planned components:

- `IndexSearch` (live component): owns the active filter chips and the open/closed state of the
  mega-menu; renders the single input/div and, when open, the mega-menu with filters grouped by
  kind (simple / preset / saved) in columns, mirroring the VisualSearch/Odoo layout.
  - receives the index definition's `filters`/`presets` (read-only) as props;
  - exposes live actions such as `addFilter`, `removeFilter`, `editFilter`, `applyPreset`,
    `applySavedFilter`;
  - adding/applying a filter closes the mega-menu (mirrors VisualSearch/Odoo behavior) and emits
    the resulting `IndexState#criteria` (plus `sort`/`view` when a preset/saved filter changes
    them) to the table/listing component (e.g. `Table` Twig component) driving the index.
- A dedicated Stimulus controller (e.g. `search-controller`) handles DOM-level concerns: toggling
  the mega-menu visibility, closing it on outside click/escape, and focus management on the input.

### URL binding

The search component keeps the index search state mapped to the page URL (query string), reusing
the same shape already consumed by `IndexStateNormalizer`:

- each active filter maps to one entry of `IndexState#criteria` (`field`, `operator`, `value`);
- applying a preset/saved filter expands to the same `criteria` (plus optional `sort`/`view`)
  shape, so the normalizer does not need to know about presets/saved filters at query time;
- the URL is the single source of truth for a given search: reloading it, sharing it, or using
  browser back/forward reconstructs the same `IndexState` deterministically, after going through
  `IndexStateNormalizer` (unknown/stale filters are dropped the same way as today).

### Initial implementation (current state)

The search UI is implemented as its own, standalone Live Component,
`WebDevelovers\ResourceBundle\Twig\Components\IndexSearch` (template:
`templates/components/index_search.html.twig`), embedded by `Table`
(`templates/components/table.html.twig`) between the "create + title" block and the
"bulk-actions / pagination / views" block, but not tied to it: any index-driven component can
embed `resource_crud_index_search` the same way, passing it the `index` name, a `resourceName`
(used to resolve field labels) and the current `criteria`.

- `IndexSearch` owns the filter-editing UI state (`criteria`, plus the `draftField` /
  `draftOperator` / `draftValue` props driving the "currently being edited" filter) and reads the
  available simple filters straight from the matching `IndexDefinitionInterface` (via `index`);
  it has **no dependency on `Table`** or on any specific view;
- `selectDraftField(field)` selects which filter is being configured in the mega-menu;
  `addCriterion()` appends it to its own `criteria` **and clears the draft**, which closes the
  mega-menu editor (matching the "picking a filter closes the menu" requirement);
  `removeCriterion(index)` / `resetCriteria()` remove one or all active filters;
- every time `criteria` changes, `IndexSearch` communicates it to the outside world the way
  [documented by Symfony UX Live Components](https://symfony.com/bundles/ux-live-component/current/index.html#communication-between-components-emitting-events):
  it calls `emitUp('index_search:changed', ['criteria' => json_encode($this->criteria)])`, which
  only reaches **parent** components (`emitUp`, not a broadcast `emit`) — loosely coupling it to
  whichever component embeds it;
- `Table` listens for that event with `#[LiveListener('index_search:changed')]`
  (`onSearchCriteriaChanged`), decodes the JSON payload, stores it in its own URL-bound `criteria`
  `LiveProp` and resets pagination — this is where the URL binding and the actual
  query/`IndexState` building still live, exactly as before;
- the template renders the single input/div as a Bootstrap `dropdown`: the "input" itself is the
  chips container (one badge per active criterion, each with its own remove button), and the
  dropdown-menu is the mega-menu, with a filter list column and a value-editor column (operator
  select + a type-aware value input: text, yes/no select for `boolean`, date for `date_range`).
  No extra Stimulus controller/JS was required for open/close, since Bootstrap's own
  `data-bs-toggle="dropdown"` already provides this, and the menu naturally re-closes once the
  `IndexSearch` component re-renders after `addCriterion()`;
- the chip is split into three visually distinct zones, in order: the filter label/value
  (`wd-index-search-criterion-label`, now purely informational/non-interactive), a dedicated
  "edit" (pencil) icon (`wd-index-search-criterion-edit`), and — separated by a vertical divider —
  the "remove" zone (`wd-index-search-criterion-remove-wrapper`), whose **entire** area (not just
  the tiny "x" glyph inside it) is clickable and carries the slightly darker background (even
  darker on hover/focus), so "actionable to remove" now means the whole zone to the right of the
  divider, not a small `btn-close` target; the inner `<button class="btn-close">` is kept only as
  a decorative glyph (`pointer-events: none`, `tabindex="-1"`, `aria-hidden="true"`), the actual
  `removeCriterion(index)` action/`aria-label`/`title` live on the wrapper `<span>` itself;
- both the edit and remove zones use the Stimulus action modifier `live#action:stop` (not the
  removed `live#action|stop` pipe syntax from older UX versions) to stop the click from bubbling
  up to the chips container's own `data-bs-toggle="dropdown"`, which was reopening the mega-menu
  in a "fresh/add" state instead of actually entering edit mode or just removing the filter — this
  is also why editing is now triggered by a **dedicated icon** instead of a click anywhere on the
  label: letting the click bubble (as the label used to do) raced with Bootstrap's own dropdown
  toggle and the Live Component re-render, so the menu sometimes opened without being pre-filled;
  stopping propagation on a small, explicit edit target removes that race;
- clicking the edit icon calls `editCriterion(index)`: it reuses the very same mega-menu used to
  add a filter, but pre-fills `draftField` / `draftOperator` / `draftValue` from the clicked
  criterion and remembers `editingIndex`, so `addCriterion()` now *updates*
  `criteria[editingIndex]` in place instead of appending a new entry whenever `editingIndex` is
  set (reset back to `null` by `resetDraft()`);
- while the mega-menu is open, the value input/select reacts to the `keydown.enter` Stimulus
  event (`data-action="keydown.enter->live#action"`, `data-live-action-param="addCriterion"`),
  so a filter can be applied by pressing <kbd>Enter</kbd> after typing/choosing a value, without
  reaching for the "apply" button;
- the value input/select is now explicitly bound to the server-side `draftValue` on render too
  (`value="{{ this.draftValue }}"` for the text/date inputs, a conditional `selected` on the
  boolean `<option>`s), not just via the `data-model` two-way binding: without it, re-opening the
  mega-menu through `editCriterion(index)` correctly pre-selected the filter in the left column
  but rendered an **empty** value editor on the right, since a `data-model`-bound input is only
  ever set by the Stimulus controller on user input, not by the server-rendered HTML.

Not implemented yet (left as-is, see Goals/Relationship above for the target design):

- **presets** and **saved filters** (only simple filters are selectable from the mega-menu so
  far).

### Preserving search state across navigation (back / previous-next)

To support "go back" and "previous/next record" from within a show page without losing the
search in progress, the existing `IndexState` / `IndexStateNormalizer` pair is reused/adapted as
the state carrier:

- the normalized `IndexState` (not just the raw query string) is the unit of persistence;
- when the user opens a record from a search result, the originating `IndexState` is retained
  (e.g. passed through the URL back to the index page, and/or kept in a short-lived storage keyed
  by index name) so that returning to the index, or moving to the previous/next record, replays
  the same filters/sort/page instead of resetting the search;
- this storage is intentionally an extension point (see below): it can start as a simple
  server-side session/URL round-trip and evolve towards a richer client-side store without
  changing the index definition contract.

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