<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use WebDevelovers\ResourceBundle\Action\ResourceActionCollector;
use WebDevelovers\ResourceBundle\Action\ResourceActionDescriptor;
use WebDevelovers\ResourceBundle\Action\ResourceActionType;
use WebDevelovers\ResourceBundle\Index\DataProvider\IndexDataProviderInterface;
use WebDevelovers\ResourceBundle\Index\DataProvider\Option\FilterOptionProviderInterface;
use WebDevelovers\ResourceBundle\Index\DataProvider\Result\IndexResult;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Registry\IndexRegistryInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexState;
use WebDevelovers\ResourceBundle\Index\State\IndexStateNormalizerInterface;
use WebDevelovers\ResourceBundle\Index\View\IndexViewInterface;
use WebDevelovers\ResourceBundle\Index\View\IndexViewFactoryInterface;

use function array_values;
use function ceil;
use function intdiv;
use function max;
use function min;

#[AsLiveComponent('resource_crud_index_table', template: '@WebDeveloversResource/components/table.html.twig')]
final class Table extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $index;

    #[LiveProp]
    public string $resourceAlias;

    #[LiveProp]
    public string|null $section = null;

    #[LiveProp]
    public string|null $routeNamePrefix = null;

    /** @var array<string, mixed> */
    #[LiveProp]
    public array $vars = [];

    #[LiveProp]
    public string $title = '';

    /** @var array<string, string> */
    #[LiveProp]
    public array $routes = [];

    #[LiveProp]
    public string $template = 'crud/index.html.twig';

    /** @var array<int, array<string, mixed>> */
    #[LiveProp(writable: true, url: true)]
    public array $criteria = [];

    /** @var array<string, string> */
    #[LiveProp(writable: true, url: true)]
    public array $sort = [];

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveProp(writable: true, url: true)]
    public int $perPage = 25;

    #[LiveProp(writable: true, url: true)]
    public int $offset = 0;

    #[LiveProp(writable: true, url: true)]
    public string $view = 'table';

    /** @var array<string, mixed> */
    #[LiveProp(writable: true)]
    public array $selectedIds = [];

    #[LiveProp]
    public int $selectedCount = 0;

    #[LiveProp(writable: true, onUpdated: 'applyPagination')]
    public int $firstItemInput = 1;

    #[LiveProp(writable: true, onUpdated: 'applyPagination')]
    public int $lastItemInput = 1;

    #[LiveProp(writable: true)]
    public string $draftField = '';

    #[LiveProp(writable: true)]
    public string $draftOperator = 'contains';

    #[LiveProp(writable: true)]
    public mixed $draftValue = null;

    #[LiveProp(writable: true)]
    public int $draftVersion = 0;

    public function __construct(
        private readonly IndexRegistryInterface $indexRegistry,
        private readonly IndexStateNormalizerInterface $stateNormalizer,
        private readonly IndexDataProviderInterface $dataProvider,
        private readonly IndexViewFactoryInterface $viewFactory,
        private readonly RouterInterface $router,
        private readonly FilterOptionProviderInterface $filterOptionProvider,
        private readonly ResourceActionCollector $actionCollector,
    ) {
    }

    public function __set(string $name, mixed $value): void
    {
        if ($name === 'selectedIds') {
            $this->selectedIds = $value;
            $this->updateSelectedCount();
        }
    }

    /**
     * @param array<string, mixed> $vars
     * @param array<string, string> $routes
     * @param array<int, array<string, mixed>> $criteria
     * @param array<string, string> $sort
     */
    public function mount(
        string $index,
        string $resourceAlias,
        string|null $section = null,
        string|null $routeNamePrefix = null,
        array $vars = [],
        string $title = '',
        array $routes = [],
        string $defaultView = 'table',
        string|null $template = null,
        array $criteria = [],
        array $sort = [],
        int $page = 1,
        int $perPage = 25,
    ): void {
        $this->index = $index;
        $this->resourceAlias = $resourceAlias;
        $this->section = $section;
        $this->routeNamePrefix = $routeNamePrefix;
        $this->vars = $vars;
        $this->title = $title;
        $this->routes = $routes;
        $this->view = $defaultView;
        $this->template = $template ?? 'crud/index.html.twig';
        $this->criteria = $criteria;
        $this->sort = $sort;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->offset = max(0, ($this->page - 1) * $this->perPage);

        $this->resolveDisplayValues();
        $this->syncPaginationInputs();
        $this->updateSelectedCount();
    }

    public function getDefinition(): IndexDefinitionInterface
    {
        return $this->indexRegistry->get($this->index);
    }

    public function getState(): IndexState
    {
        return $this->stateNormalizer->normalize(
            new IndexState(
                criteria: $this->criteria,
                sort: $this->sort,
                page: $this->page,
                perPage: $this->perPage,
                view: $this->view,
                offset: $this->offset,
            ),
            $this->getDefinition(),
        );
    }

    #[LiveAction]
    public function addCriterion(): void
    {
        $field = $this->draftField;
        $operator = $this->draftOperator !== '' ? $this->draftOperator : 'equals';
        $value = $this->draftValue;

        if ($field === '') {
            return;
        }

        if (is_string($value) && ($value !== '')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['value'])) {
                $originalValue = $value;
                $value = $decoded['value'];
            }
        }

        $this->criteria[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'display_value' => $originalValue ?? $value,
        ];

        $this->draftField = '';
        $this->draftOperator = 'contains';
        $this->draftValue = null;
        ++$this->draftVersion;

        $this->resetPage();
    }

    #[LiveAction]
    public function removeCriterion(#[LiveArg]
    int $index,): void
    {
        if (! isset($this->criteria[$index])) {
            return;
        }

        unset($this->criteria[$index]);
        $this->criteria = array_values($this->criteria);

        $this->resetPage();
    }

    #[LiveAction]
    public function resetCriteria(): void
    {
        $this->criteria = [];
        $this->draftField = '';
        $this->draftOperator = 'contains';
        $this->draftValue = null;
        ++$this->draftVersion;

        $this->resetPage();
    }

    public function isSortable(string $field): bool
    {
        $sorts = $this->getDefinition()->getSorts();

        return isset($sorts[$field]);
    }

    public function getSortDirectionFor(string $field): string|null
    {
        if (! $this->isSortable($field)) {
            return null;
        }

        return $this->sort[$field] ?? null;
    }

    #[LiveAction]
    public function toggleSort(#[LiveArg]
    string $field,): void
    {
        if (! $this->isSortable($field)) {
            return;
        }

        $currentDirection = $this->getSortDirectionFor($field);

        if ($currentDirection === null) {
            $this->sort = [$field => 'asc'];
        } elseif ($currentDirection === 'asc') {
            $this->sort = [$field => 'desc'];
        } else {
            $this->sort = [];
        }

        $this->resetPage();
    }

    public function getResult(): IndexResult
    {
        return $this->dataProvider->provide(
            $this->getDefinition(),
            $this->getState(),
            [
                'resource_alias' => $this->resourceAlias,
                'section' => $this->section,
                'route_name_prefix' => $this->routeNamePrefix,
            ],
        );
    }

    public function getIndexView(): IndexViewInterface
    {
        $state = $this->getState();
        $result = $this->getResult();

        return $this->viewFactory->create(
            $this->getDefinition(),
            $state,
            $result,
        );
    }

    public function getTitleLabel(): string
    {
        return $this->title !== '' ? $this->title : 'wd.resource.unknown.plural';
    }

    public function hasCreateRoute(): bool
    {
        return $this->hasRouteName($this->routes['create'] ?? null);
    }

    public function getCreateRoute(): string|null
    {
        $routeName = $this->routes['create'] ?? null;

        if ($routeName === null || ! $this->hasRouteName($routeName)) {
            return null;
        }

        return $this->generateUrl($routeName);
    }

    public function hasItemActions(): bool
    {
        return $this->hasRoute('show')
            || $this->hasRoute('update')
            || $this->hasRoute('delete');
    }

    public function getShowRouteFor(object $resource): string|null
    {
        if (! $this->isActionGranted('show', $resource)) {
            return null;
        }

        return $this->generateItemRoute('show', $resource);
    }

    public function getUpdateRouteFor(object $resource): string|null
    {
        return $this->generateItemRoute('update', $resource);
    }

    public function getDeleteRouteFor(object $resource): string|null
    {
        return $this->generateItemRoute('delete', $resource);
    }

    public function getFirstItem(): int
    {
        $result = $this->getResult();

        if ($result->totalItems === 0) {
            return 0;
        }

        return $this->getState()->offset + 1;
    }

    public function getLastItem(): int
    {
        $result = $this->getResult();

        return min($this->getState()->offset + $result->perPage, $result->totalItems);
    }

    public function getLastPage(): int
    {
        $result = $this->getResult();

        return max(1, (int) ceil($result->totalItems / $result->perPage));
    }

    public function hasPreviousPage(): bool
    {
        return $this->getState()->offset > 0;
    }

    public function hasNextPage(): bool
    {
        return $this->getState()->page < $this->getLastPage();
    }

    #[LiveAction]
    public function nextPage(): void
    {
        if (! $this->hasNextPage()) {
            return;
        }

        $this->offset += $this->perPage;
        $this->page = intdiv($this->offset, $this->perPage) + 1;
        $this->syncPaginationInputs();
    }

    #[LiveAction]
    public function previousPage(): void
    {
        $this->offset = max(0, $this->offset - $this->perPage);
        $this->page = intdiv($this->offset, $this->perPage) + 1;
        $this->syncPaginationInputs();
    }

    #[LiveAction]
    public function resetPage(): void
    {
        $this->page = 1;
        $this->offset = 0;
        $this->syncPaginationInputs();
    }

    #[LiveAction]
    public function applyPagination(): void
    {
        $totalItems = $this->getResult()->totalItems;

        if ($totalItems === 0) {
            $this->page = 1;
            $this->perPage = max(1, $this->perPage);
            $this->offset = 0;
            $this->syncPaginationInputs();

            return;
        }

        $firstItem = max(1, min($this->firstItemInput, $totalItems));
        $lastItem = max($firstItem, min($this->lastItemInput, $totalItems));

        $this->perPage = max(1, $lastItem - $firstItem + 1);
        $this->offset = $firstItem - 1;
        $this->page = intdiv($this->offset, $this->perPage) + 1;

        $this->syncPaginationInputs();
    }

    #[LiveAction]
    public function toggleAll(): void
    {
        $result = $this->getResult();
        $currentPageIds = [];
        $currentPageSelectedCount = 0;

        foreach ($result->items as $item) {
            $id = '';
            if (is_object($item)) {
                $id = method_exists($item, 'getId') ? (string) $item->getId() : (string) ($item->id ?? '');
            }
            if ($id === '') {
                continue;
            }
            $currentPageIds[] = $id;

            if ($this->isIdSelected($id)) {
                $currentPageSelectedCount++;
            }
        }

        $currentPageCount = count($currentPageIds);
        $allSelected = ($currentPageCount > 0 && $currentPageSelectedCount === $currentPageCount);

        foreach ($currentPageIds as $id) {
            if ($allSelected) {
                unset($this->selectedIds[$id]);
            } else {
                $this->selectedIds[$id] = true;
            }
        }

        $this->updateSelectedCount();
    }

    public function updateSelectedCount(): void
    {
        $this->selectedCount = count($this->getSelectedIdsList());
    }

    public function isIdSelected(string|int $id): bool
    {
        $selected = $this->selectedIds[(string) $id] ?? false;

        return $selected === true || $selected === 'true' || $selected === '1' || $selected === 1 || $selected === 'on';
    }

    /** @return list<string> */
    public function getSelectedIdsList(): array
    {
        $ids = [];
        foreach ($this->selectedIds as $id => $selected) {
            if ($this->isIdSelected($id)) {
                $ids[] = (string) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /** @return list<ResourceActionDescriptor> */
    public function getBulkActions(): array
    {
        return $this->actionCollector->forResourceAlias(
            $this->resourceAlias,
            ResourceActionType::BULK,
        );
    }

    private function syncPaginationInputs(): void
    {
        $this->firstItemInput = $this->getFirstItem();
        $this->lastItemInput = $this->getLastItem();
    }

    private function hasRoute(string $action): bool
    {
        return $this->hasRouteName($this->routes[$action] ?? null);
    }

    private function hasRouteName(string|null $routeName): bool
    {
        if ($routeName === null || $routeName === '') {
            return false;
        }

        return $this->router->getRouteCollection()->get($routeName) !== null;
    }

    private function generateItemRoute(string $action, object $resource): string|null
    {
        $routeName = $this->routes[$action] ?? null;

        if ($routeName === null || ! $this->hasRouteName($routeName)) {
            return null;
        }

        $id = method_exists($resource, 'getId') ? (string) $resource->getId() : (string) ($resource->id ?? '');

        return $this->generateUrl($routeName, [
            'id' => $id,
        ]);
    }

    private function isActionGranted(string $action, object $resource): bool
    {
        return $this->isGranted($this->resourceAlias . '.' . $action, $resource);
    }

    private function resolveDisplayValues(): void
    {
        $definition = $this->getDefinition();
        $filters = $definition->getFilters();

        foreach ($this->criteria as $i => $criterion) {
            $field = $criterion['field'] ?? null;
            if (! isset($filters[$field])) {
                continue;
            }

            $filter = $filters[$field];
            $type = $filter->getType();

            if ($type !== 'entity' && $type !== 'choice') {
                continue;
            }

            // Se display_value è già un JSON con label, non facciamo nulla (probabilmente appena aggiunto)
            $displayValue = $criterion['display_value'] ?? null;
            if (is_string($displayValue) && str_starts_with($displayValue, '{') && str_contains($displayValue, '"label"')) {
                continue;
            }

            $value = $criterion['value'];
            $selected = is_array($value) ? $value : [$value];
            $selected = array_map(static fn ($v): string => (string) $v, $selected);

            $options = $this->filterOptionProvider->getOptions($definition, $field, '', $selected);
            if ($options === []) {
                continue;
            }

            if (is_array($value)) {
                $labels = [];
                $values = [];
                foreach ($options as $option) {
                    if (in_array((string) $option['value'], $selected, true)) {
                        $labels[] = $option['label'];
                        $values[] = $option['value'];
                    }
                }
                if ($labels !== []) {
                    $this->criteria[$i]['display_value'] = json_encode(['value' => $values, 'label' => $labels]);
                }
            } else {
                foreach ($options as $option) {
                    if ((string) $option['value'] === (string) $value) {
                        $this->criteria[$i]['display_value'] = json_encode(['value' => $option['value'], 'label' => $option['label']]);
                        break;
                    }
                }
            }
        }
    }
}
