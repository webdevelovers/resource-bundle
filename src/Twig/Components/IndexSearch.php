<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use WebDevelovers\ResourceBundle\Index\DataProvider\Option\FilterOptionProviderInterface;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\FilterDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinitionInterface;
use WebDevelovers\ResourceBundle\Index\Registry\IndexRegistryInterface;

use function array_map;
use function array_values;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function str_contains;
use function str_starts_with;

/**
 * Standalone, reusable "search bar" live component for the index system
 * (VisualSearch/Odoo-like single input + mega-menu of simple filters).
 *
 * It is intentionally decoupled from any specific index view (table, kanban,
 * ...): it only needs the `index` name (to read the available filters from
 * the matching `IndexDefinitionInterface`) and an initial set of `criteria`.
 * It never talks directly to the view that embeds it; instead, every time
 * the active criteria change it emits an `index_search:changed` event
 * *upwards*, so the parent component (e.g. the `Table` live component) can
 * react (store the new criteria, refetch results, update the URL, ...).
 */
#[AsLiveComponent('resource_crud_index_search', template: '@WebDeveloversResource/components/index_search.html.twig')]
final class IndexSearch
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public string $index;

    #[LiveProp]
    public string $resourceName = '';

    /** @var array<int, array<string, mixed>> */
    #[LiveProp(writable: true)]
    public array $criteria = [];

    #[LiveProp(writable: true)]
    public string $draftField = '';

    #[LiveProp(writable: true)]
    public string $draftOperator = 'contains';

    #[LiveProp(writable: true)]
    public mixed $draftValue = null;

    #[LiveProp(writable: true)]
    public int $draftVersion = 0;

    #[LiveProp(writable: true)]
    public int|null $editingIndex = null;

    public function __construct(
        private readonly IndexRegistryInterface $indexRegistry,
        private readonly FilterOptionProviderInterface $filterOptionProvider,
    ) {
    }

    /** @param array<int, array<string, mixed>> $criteria */
    public function mount(string $index, string $resourceName = '', array $criteria = []): void
    {
        $this->index = $index;
        $this->resourceName = $resourceName;
        $this->criteria = $criteria;

        $this->resolveDisplayValues();
    }

    public function getDefinition(): IndexDefinitionInterface
    {
        return $this->indexRegistry->get($this->index);
    }

    #[LiveAction]
    public function selectDraftField(#[LiveArg]
    string $field,): void
    {
        $filters = $this->getDefinition()->getFilters();

        if (! isset($filters[$field])) {
            return;
        }

        $this->draftField = $field;
        $this->draftOperator = $this->getDefaultOperatorFor($filters[$field]->getType());
        $this->draftValue = null;
        $this->editingIndex = null;
        ++$this->draftVersion;
    }

    #[LiveAction]
    public function clearDraftField(): void
    {
        $this->resetDraft();
    }

    /**
     * Opens the mega-menu pre-filled with an existing criterion, so the
     * user can edit its operator/value instead of only being able to
     * remove and re-add it.
     */
    #[LiveAction]
    public function editCriterion(#[LiveArg]
    int $index,): void
    {
        if (! isset($this->criteria[$index])) {
            return;
        }

        $criterion = $this->criteria[$index];
        $filters = $this->getDefinition()->getFilters();
        $field = $criterion['field'] ?? '';

        if (! isset($filters[$field])) {
            return;
        }

        $this->draftField = $field;
        $this->draftOperator = $criterion['operator'] ?? $this->getDefaultOperatorFor($filters[$field]->getType());
        $this->draftValue = $criterion['display_value'] ?? $criterion['value'] ?? null;
        $this->editingIndex = $index;
        ++$this->draftVersion;
    }

    public function getDraftFilter(): FilterDefinitionInterface|null
    {
        if ($this->draftField === '') {
            return null;
        }

        return $this->getDefinition()->getFilters()[$this->draftField] ?? null;
    }

    /** @return array<string, string> */
    public function getOperatorsFor(string $type): array
    {
        return match ($type) {
            'boolean' => ['equals' => 'wd.resource.index.search.operator.equals'],
            'entity' => [
                'equals' => 'wd.resource.index.search.operator.equals',
                'in' => 'wd.resource.index.search.operator.in',
            ],
            'enum', 'choice' => ['equals' => 'wd.resource.index.search.operator.equals'],
            'date_range' => [
                'gte' => 'wd.resource.index.search.operator.gte',
                'lte' => 'wd.resource.index.search.operator.lte',
            ],
            default => [
                'contains' => 'wd.resource.index.search.operator.contains',
                'equals' => 'wd.resource.index.search.operator.equals',
                'not_equals' => 'wd.resource.index.search.operator.not_equals',
                'starts_with' => 'wd.resource.index.search.operator.starts_with',
                'ends_with' => 'wd.resource.index.search.operator.ends_with',
            ],
        };
    }

    private function getDefaultOperatorFor(string $type): string
    {
        return match ($type) {
            'boolean', 'entity', 'enum', 'choice' => 'equals',
            'date_range' => 'gte',
            default => 'contains',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public function getDraftOptions(): array
    {
        $filter = $this->getDraftFilter();

        if ($filter === null || ! in_array($filter->getType(), ['enum', 'choice', 'entity'], true)) {
            return [];
        }

        return $this->filterOptionProvider->getOptions($this->getDefinition(), $this->draftField);
    }

    /** @param array<string, mixed> $criterion */
    public function getCriterionDisplayValue(array $criterion): string
    {
        $displayValue = $criterion['display_value'] ?? $criterion['value'] ?? null;

        if (is_string($displayValue) && str_starts_with($displayValue, '{') && str_contains($displayValue, '"label"')) {
            $decoded = json_decode($displayValue, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['label'])) {
                $label = $decoded['label'];

                return is_array($label) ? implode(', ', $label) : (string) $label;
            }
        }

        $filters = $this->getDefinition()->getFilters();
        $filter = $filters[$criterion['field'] ?? ''] ?? null;

        if ($filter !== null && $filter->getType() === 'boolean') {
            return $this->normalizeBooleanDisplayValue($displayValue);
        }

        if (is_array($displayValue)) {
            return implode(', ', array_map(static fn ($v): string => (string) $v, $displayValue));
        }

        return (string) $displayValue;
    }

    private function normalizeBooleanDisplayValue(mixed $value): string
    {
        return match (true) {
            $value === true, $value === 'true', $value === '1', $value === 1, $value === 'yes', $value === 'si' => 'wd.resource.index.search.value.yes',
            default => 'wd.resource.index.search.value.no',
        };
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

        $originalValue = $value;

        if (is_string($value) && ($value !== '')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['value'])) {
                $value = $decoded['value'];
            }
        }

        $criterion = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'display_value' => $originalValue,
        ];

        if ($this->editingIndex !== null && isset($this->criteria[$this->editingIndex])) {
            $this->criteria[$this->editingIndex] = $criterion;
        } else {
            $this->criteria[] = $criterion;
        }

        $this->resetDraft();
        $this->emitCriteriaChanged();
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

        $this->emitCriteriaChanged();
    }

    #[LiveAction]
    public function resetCriteria(): void
    {
        $this->criteria = [];
        $this->resetDraft();

        $this->emitCriteriaChanged();
    }

    private function resetDraft(): void
    {
        $this->draftField = '';
        $this->draftOperator = 'contains';
        $this->draftValue = null;
        $this->editingIndex = null;
        ++$this->draftVersion;
    }

    private function emitCriteriaChanged(): void
    {
        $this->emitUp('index_search:changed', ['criteria' => json_encode($this->criteria)]);
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

            if ($type !== 'entity' && $type !== 'choice' && $type !== 'enum') {
                continue;
            }

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
