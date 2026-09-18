# Resource Actions

Resource actions are a generic abstraction for "things that can happen" to a resource.

CRUD actions are an opinionated subset of this model. Transitions are another subset (typically integrated with state machines/workflows).

## Why this abstraction exists

A route can represent more than just "open a page". It can represent an action entry point with UI metadata and runtime conditions.

This allows application templates to render actions consistently while still supporting custom behavior.

## Action categories

The bundle exposes `ResourceActionType`:

- `crud`: standard actions such as `index`, `show`, `create`, `update`, `delete`.
- `transition`: state transitions (for example, via Symfony Workflow).
- `extra`: custom actions outside standard CRUD.
- `bulk`: mass actions.

## Route-level action metadata

A route can expose action metadata in the `_resource_action` default. Typical fields:

- `type`: one of `ResourceActionType` values.
- `label`: translation key or plain label.
- `icon`: icon identifier used by your UI.
- `buttonClass`: CSS classes for action button rendering.
- `priority`: optional sort priority.
- `supports`: class implementing `SupportsResourceActionInterface` for runtime availability checks.
- `requiresConfirmation`: marks actions that require user confirmation.
- `modal`: modal configuration for advanced UI flows.

`supports` contract:

```php
interface SupportsResourceActionInterface
{
    public function supports(ResourceInterface $resource, RequestConfiguration $requestConfiguration): bool;
}
```

Use it to enable/disable an action for a specific resource instance and request context.

## UI interaction patterns

### 1) Direct action

User clicks and the action is executed immediately.

### 2) Confirmation before execution

Use `requiresConfirmation: true` when the frontend must show a confirm step before sending the request.

### 3) Modal with additional interaction

Use `modal` metadata when the action needs an additional UI step before execution.
For example, open a Bootstrap modal with an internal Symfony UX Live Component that collects extra data.

## Examples

### Extra action with runtime support

```php
#[Route(
    path: '/subscriptions/{id}/archive',
    name: 'wd_subscriptions_subscription_archive',
    requirements: [
        'id' => Requirement::UUID,
    ],
    defaults: [
        '_resource_alias' => 'wd.subscription',
        '_resource_action' => [
            'type' => ResourceActionType::EXTRA->value,
            'label' => 'wd.resource.subscription.action.archive.label',
            'icon' => 'tabler:archive',
            'buttonClass' => 'btn btn-danger',
            'supports' => ArchiveSupports::class,
        ],
        '_wd' => ['section' => 'subscriptions'],
    ],
    methods: ['POST'],
)]
```

### Extra action opening a modal with component

```php
#[Route(
    path: '/subscriptions/{id}/renew-administrative',
    name: 'wd_subscriptions_subscription_renew_administrative',
    requirements: [
        'id' => Requirement::UUID,
    ],
    defaults: [
        '_resource_alias' => 'wd.subscription',
        '_resource_action' => [
            'type' => ResourceActionType::EXTRA->value,
            'label' => 'Administrative renewal',
            'icon' => 'tabler:file-invoice',
            'buttonClass' => 'btn btn-outline-primary',
            'modal' => [
                'id' => 'subscription-renew-administrative-modal',
                'internalComponent' => 'Subscription:RenewSubscriptionDates',
            ],
            'supports' => RenewalsSupports::class,
        ],
        '_wd' => ['section' => 'subscriptions'],
    ],
    methods: ['POST'],
)]
```

### Extra action with explicit confirmation

```php
#[Route(
    path: '/commissions/{id}/pay',
    name: 'wd_crm_commission_pay',
    requirements: [
        'id' => Requirement::UUID,
    ],
    defaults: [
        '_resource_alias' => 'wd.commission',
        '_resource_action' => [
            'type' => ResourceActionType::EXTRA->value,
            'label' => 'wd.action.pay',
            'icon' => 'bi:credit-card',
            'buttonClass' => 'btn btn-success btn-sm',
            'priority' => 10,
            'requiresConfirmation' => true,
            'supports' => PaySupport::class,
        ],
        '_wd' => ['section' => 'crm'],
    ],
    methods: ['GET', 'POST'],
)]
```

## CRUD as opinionated actions (and app-level overrides)

The `wd.resource` loader currently generates opinionated CRUD routes and `_wd` defaults automatically.

At application level, you can always define/override specific routes and add `_resource_action` metadata to enrich UX behavior (confirmation, modal, component-driven input, custom ordering, etc.).

This keeps a strong default for common CRUD projects, while preserving freedom for advanced action flows.

## Create action flow (`entity-form` and `dto-form`)

For a full controller-oriented reference, see `docs/controllers-create.md`.

The `Create` controller supports two input strategies driven by route `_wd` configuration:

- `entity-form`: no `input` configured, form data is the resource entity itself.
- `dto-form`: `input` configured with a DTO class, then mapped to the resource entity before dispatch.

Example:

```yaml
alias: app.product
form:
  type: App\Form\ProductType
input: App\DTO\ProductInput
```

### Template interaction mode

The same action can be rendered with a standard Symfony form or a Symfony UX Live Component.
The frontend strategy is exposed to templates via context:

- `form_mode`: `entity-form` or `dto-form`
- `is_live_component`: boolean (resolved from `_wd.vars.live_component` or `_wd.vars.create.live_component`)

## Create action redirect behavior

Redirect is always resolved through `RequestConfiguration` and `RedirectHandler`, so the route can define:

- redirect to `show` (default, when available)
- fallback to `index`
- explicit custom redirect route (for example `update`/`edit`/custom)
- referer/header redirect modes

Example:

```yaml
alias: app.product
redirect:
  route: app_product_update
```

## Pluggable action lifecycle events

Create action emits lifecycle events through Symfony Event Dispatcher, with two names for each stage:

- global: `wd.resource.action.{action}.{stage}`
- scoped: `wd.resource.action.{resourceAlias}.{action}.{stage}`

For `app.product` create:

- `wd.resource.action.create.initialized`
- `wd.resource.action.app.product.create.initialized`

Current create stages:

- `initialized`
- `request_handled`
- `before_dispatch`
- `dispatched` (sync)
- `dispatched_async` (async)
- `error`

Event payload (`ResourceActionEvent`) includes:

- `configuration`
- `action`
- `stage`
- `resource` (when available)
- `input` (form/input object)
- `error` (when stage is `error`)
- `context` (extra data)
