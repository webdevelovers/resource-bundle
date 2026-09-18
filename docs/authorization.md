# Authorization

The bundle provides a generic `AuthorizationChecker` that uses route configuration (`RequestConfiguration`) to resolve the security attribute to check.

## How the attribute is resolved

The check is invoked by CRUD actions with a logical action name (`create`, `update`, `delete`, `show`, or a transition name).

Resolution rules:

1. `permission` missing or `false`: no authorization check is executed.
2. `permission: true`: automatic attribute in the format `application.resource.action`.
3. `permission` as a string: the string is used as-is.

## Attributes exposed by standard actions

With `permission: true` and alias `app.product`, standard checks are:

- `app.product.create`
- `app.product.update`
- `app.product.delete`
- `app.product.show`

For transitions (`ApplyTransition`), the suffix is the requested transition name.

## Route configuration examples

Automatic permission per action:

```yaml
alias: app.product
permission: true
```

Fixed custom permission:

```yaml
alias: app.product
permission: product.manage
```

Disable authorization checks for that route:

```yaml
alias: app.product
permission: false
# or simply omit `permission`
```

## Symfony voter integration

The resolved attribute is passed to `Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::isGranted()` together with the subject (if present). This means you can implement Symfony voters (or other security strategies) using exactly the attributes above.
