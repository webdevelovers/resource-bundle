# CRUD Controller: `Create`

This document describes the current `Create` CRUD controller behavior and route-driven configuration model.

## Scope

`Create` is the reference implementation for CRUD action controllers in this bundle:

- route configuration drives behavior (`_wd` values through `RequestConfiguration`)
- authorization is checked before processing
- form handling supports both entity and DTO input modes
- persistence is executed through the resource message bus (sync or async)
- rendering and redirects are delegated to dedicated controller services
- lifecycle events are dispatched for pluggability
- failures are handled defensively to avoid exposing a `500` to end users

## Execution flow

High-level flow:

1. Resolve action name (`create`).
2. Check authorization with `AuthorizationCheckerInterface`.
3. Build the form object (`entity-form` or `dto-form`).
4. Dispatch `initialized` event.
5. Build and handle the Symfony form.
6. Dispatch `request_handled` event.
7. If submitted and valid:
   - map DTO to resource when needed
   - dispatch `before_dispatch`
   - dispatch command sync or async
   - dispatch `dispatched` / `dispatched_async`
   - redirect
8. If not submitted or invalid, render template with contextual data.
9. On exception, dispatch `error` and fallback redirect to index.

## Input strategies

### `entity-form`

Used when `_wd.input` is not configured.

- form object: resource entity instance
- persistence object: same entity instance

### `dto-form`

Used when `_wd.input` is configured.

- form object: configured DTO class instance
- persistence object: DTO mapped to resource through `DTOMapperInterface`

Example:

```yaml
alias: app.product
form:
  type: App\Form\ProductType
input: App\DTO\ProductInput
```

## Sync vs async dispatch

Dispatch mode is controlled by route parameters (`_wd.async`):

- `true` / `false` for global action behavior
- or action map form (for example `async: { create: true }`)

Behavior:

- sync: `dispatchCreate()` returns created resource and controller redirects with resource-aware strategy
- async: `dispatchCreateAsync()` is fire-and-forget and controller redirects to index

## Rendering context

When rendering, the controller provides default template variables:

- `configuration`
- `metadata`
- `resource`
- `input`
- `action`
- `form_mode` (`entity-form` or `dto-form`)
- `is_live_component`
- `<metadata.name>` alias variable (resource shortcut)
- `form` (form view)

`is_live_component` is derived from:

- `_wd.vars.live_component`
- `_wd.vars.create.live_component`

## Redirect behavior

Redirect logic is delegated to `RedirectHandlerInterface` and uses route configuration (`redirect`, fallbacks, referer/header modes).

Typical outcomes:

- sync success: redirect to resource-aware destination (commonly `show`, fallback `index`)
- async success: redirect to `index`
- error fallback: redirect to `index`

## Security

`Create` calls:

```php
$authorizationChecker->denyAccessUnlessGranted('create', $configuration);
```

Permission resolution rules are documented in `docs/authorization.md`.

## Live component note

Current `Create` submission logic requires a non-XHR POST to enter persistence flow.
This means a typical AJAX submit from a Symfony UX Live Component does not pass through standard create dispatch automatically.

Current recommended approach:

- keep `Create` for classic Symfony form submits
- handle fully interactive Live Component submit server-side inside the component action

## Error handling

The controller catches both `ResourceBusException` and generic `\Throwable`:

- emits `error` lifecycle event
- returns safe redirect fallback (`index`)

This prevents exposing internal failures directly as uncaught controller errors.

## Related docs

- `docs/actions.md` (resource action model and lifecycle events)
- `docs/routing.md` (route generation and `_wd` defaults)
- `docs/authorization.md` (permission and voter integration)