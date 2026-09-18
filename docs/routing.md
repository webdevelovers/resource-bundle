# Routing

The `wd.resource` loader automatically generates CRUD routes from resource configuration.

For the generic action model (CRUD, transitions, extra, bulk) and `_resource_action` metadata conventions, see `docs/actions.md`.
For the index system (definitions, filters, sorting, state normalization, providers), see `docs/index.md`.

## Default generated routes

With this minimal configuration:

```yaml
alias: app.product
```

the loader generates 6 routes:

- `app_product_index` → `GET /products/`
- `app_product_create` → `GET|POST /products/new`
- `app_product_show` → `GET /products/{id}`
- `app_product_update` → `GET|POST /products/{id}/edit`
- `app_product_delete` → `DELETE /products/{id}`
- `app_product_bulk_delete` → `DELETE /products/bulk-delete`

The `/products/` path is derived from the pluralized metadata name (slug).

## Filter routes (`only` / `except`)

```yaml
alias: app.product
only: [index, show]
```

Generates only:

- `app_product_index`
- `app_product_show`

Or:

```yaml
alias: app.product
except: [delete, bulkDelete]
```

Generates all default routes except delete and bulk delete.

> `only` and `except` are mutually exclusive.

## PHP configuration example

You can load routing configuration from a PHP file too.

Create a config file that returns an array, for example `config/routes/wd_product.php`:

```php
<?php

return [
    'alias' => 'app.product',
    'path' => 'catalog/products',
    'section' => 'admin',
    'route_name_prefix' => 'backoffice',
    'identifier' => 'id',
    'identifier_type' => 'uuid',
    'only' => ['index', 'show', 'create', 'update'],
    'permission' => true,
];
```

Then import it in your Symfony routes file:

```yaml
wd_product_routes:
    resource: '%kernel.project_dir%/config/routes/wd_product.php'
    type: wd.resource
```

The PHP file must return an array; otherwise the loader throws an exception.

## Auto-load from `src/Resource/Route` via interface

If you prefer organizing route configuration in dedicated PHP classes, you can place them under `src/Resource/Route` and implement `ResourceRouteInterface`.

The bundle automatically discovers and registers these route classes without requiring any configuration in `config/routes.yaml`!

```php
<?php

namespace App\Resource\Route;

use WebDevelovers\ResourceBundle\Routing\ResourceRouteInterface;

final class ProductRoute implements ResourceRouteInterface
{
    public static function config(): array
    {
        return [
            'alias' => 'app.product',
            'path' => 'catalog/products',
            'section' => 'admin',
            'only' => ['index', 'show', 'create', 'update'],
            'permission' => true,
        ];
    }
}
```

The bundle scans `src/Resource/Route`, discovers classes implementing `ResourceRouteInterface`, executes `config()`, and registers all routes automatically into Symfony's router.

### Multiple route configurations for the same resource

In real-world applications, different actions (such as `show`, `index`, `create`, `update`, `delete`) often require completely different configurations (different URLs, sections, forms, templates, redirects, or permissions).

`ResourceRouteInterface::config()` can return a list of configuration arrays:

```php
<?php

namespace App\Resource\Route;

use App\Form\ProductType;
use WebDevelovers\ResourceBundle\Routing\ResourceRouteInterface;

final class ProductRoute implements ResourceRouteInterface
{
    public static function config(): array
    {
        return [
            // Public frontend routes: read-only, custom path and templates, no permission required
            [
                'alias' => 'app.product',
                'only' => ['index', 'show'],
                'path' => 'shop/products',
                'section' => 'shop',
                'templates' => 'shop/product',
                'permission' => false,
            ],
            // Backoffice management routes: create and update with form, redirect, and permissions
            [
                'alias' => 'app.product',
                'only' => ['create', 'update'],
                'path' => 'admin/catalog/products',
                'section' => 'admin',
                'route_name_prefix' => 'manage',
                'form' => ProductType::class,
                'templates' => 'admin/product',
                'redirect' => 'index',
                'permission' => true,
            ],
            // Administrative deletion: restricted to administrators
            [
                'alias' => 'app.product',
                'only' => ['delete', 'bulkDelete'],
                'path' => 'admin/catalog/products',
                'section' => 'admin',
                'permission' => 'ROLE_ADMIN',
            ],
        ];
    }
}
```

*(Note: Manual import via `@wd.resource.tagged` in `config/routes.yaml` is also supported if you prefer explicit loading).*

### Real-world example: migrating from verbose manual routes

Consider this previous YAML configuration where each CRUD route had to be written manually to specify custom forms, templates, and view variables:

```yaml
# Previous verbose configuration in routes.yaml:
wd_settings_commission_rule_create:
  path: /settings/commission-rules/new
  methods: [ GET, POST ]
  defaults:
    _controller: wd.action.create.commission_rule
    _resource_alias: 'wd.commission_rule'
    _wd:
      form: App\Form\Commission\CommissionRuleType
      section: settings
      template: 'theme/crud/create.html.twig'
      vars:
        root_label: 'wd.settings.system_settings'
        templates:
          form: 'commission_rule/_form.html.twig'

wd_settings_commission_rule_update:
  path: /settings/commission-rules/{id}/update
  methods: [ GET, POST, PUT ]
  defaults:
    _controller: wd.action.update.commission_rule
    _resource_alias: 'wd.commission_rule'
    _wd:
      form: App\Form\Commission\CommissionRuleType
      section: settings
      template: 'theme/crud/update.html.twig'
      vars:
        root_label: 'wd.settings.system_settings'
        templates:
          form: 'commission_rule/_form.html.twig'

wd_settings_commission_rule_show:
  path: /settings/commission-rules/{id}
  methods: [ GET ]
  defaults:
    _controller: wd.action.show.commission_rule
    _resource_alias: 'wd.commission_rule'
    _wd:
      section: settings
      template: 'theme/crud/show.html.twig'
      vars:
        root_label: 'wd.settings.system_settings'
        templates:
          content: 'commission_rule/show_content.html.twig'

wd_commission_rule:
  resource: |
    alias: wd.commission_rule
    grid: app_commission_rule
    only: ['index', 'delete']
    section: settings
    templates: 'theme/crud'
    path: /settings/commission-rules
  type: wd.resource
```

In the new system, all of this can be replaced by a single PHP route class placed in `src/Resource/Route/CommissionRuleRoute.php` (with zero lines needed in `routes.yaml`):

```php
<?php

declare(strict_types=1);

namespace App\Resource\Route;

use App\Form\Commission\CommissionRuleType;
use WebDevelovers\ResourceBundle\Routing\ResourceRouteInterface;

final class CommissionRuleRoute implements ResourceRouteInterface
{
    public static function config(): array
    {
        return [
            'alias' => 'wd.commission_rule',
            'path' => 'settings/commission-rules',
            'section' => 'settings',
            'templates' => 'theme/crud',
            'form' => CommissionRuleType::class,
            'index' => 'app_commission_rule',
            'vars' => [
                'all' => [
                    'root_label' => 'wd.settings.system_settings',
                ],
                'create' => [
                    'templates' => [
                        'form' => 'commission_rule/_form.html.twig',
                    ],
                ],
                'update' => [
                    'templates' => [
                        'form' => 'commission_rule/_form.html.twig',
                    ],
                ],
                'show' => [
                    'templates' => [
                        'content' => 'commission_rule/show_content.html.twig',
                    ],
                ],
            ],
        ];
    }
}
```

This single class generates:
- `wd_settings_commission_rule_index` (`GET /settings/commission-rules/` with grid `app_commission_rule` and `theme/crud/index.html.twig`)
- `wd_settings_commission_rule_create` (`GET|POST /settings/commission-rules/new` with form, `theme/crud/create.html.twig`, and template vars)
- `wd_settings_commission_rule_show` (`GET /settings/commission-rules/{id}` with `theme/crud/show.html.twig` and show content vars)
- `wd_settings_commission_rule_update` (`GET|POST /settings/commission-rules/{id}/edit` with form, `theme/crud/update.html.twig`, and template vars)
- `wd_settings_commission_rule_delete` (`DELETE /settings/commission-rules/{id}`)

## Show content template convention

By default, the CRUD `show` template includes:

- `{resource_name}/show_content.html.twig`

where `{resource_name}` is the resource metadata name (for example `commission_rule/show_content.html.twig`).

You can still override it explicitly per route configuration:

```yaml
alias: wd.commission_rule
vars:
  show:
    templates:
      content: 'custom/commission_rule/show_content.html.twig'
```

## Customize path, route name and section

```yaml
alias: app.product
path: catalog/products
section: admin
route_name_prefix: backoffice
```

Example route names:

- `app_admin_product_backoffice_index`
- `app_admin_product_backoffice_show`

Example paths:

- `GET /catalog/products/`
- `GET /catalog/products/{id}`

## Identifier: `int` or `uuid`

Available options:

- `identifier` (default: `id`)
- `identifier_type` (default: `uuid`, allowed: `uuid` or `int`)

UUID example (default):

```yaml
alias: app.product
identifier: id
identifier_type: uuid
```

`show`, `update`, and `delete` routes will use a UUID v4-v7 requirement on `{id}`.

Integer example:

```yaml
alias: app.product
identifier: id
identifier_type: int
```

`show`, `update`, and `delete` routes will use the `\d+` requirement.

## Authorization option: `permission`

`permission` supports `false`, `true`, or a custom string:

- `false` (default) or missing: no authorization check.
- `true`: per-action automatic permission (for example `app.product.update`).
- string (for example `product.manage`): fixed custom permission.

Example:

```yaml
alias: app.product
permission: true
```

Or:

```yaml
alias: app.product
permission: product.manage
```

## `_wd` defaults applied to each route

The loader populates `_wd` defaults used by the bundle, such as:

- `_wd.section`
- `_wd.route_name_prefix`
- `_wd.input` / `_wd.output`
- `_wd.message`
- `_wd.criteria`
- `_wd.permission`
- `_wd.template`
- `_wd.redirect`

For `bulkDelete`, it also sets repository method `findById` with `$ids` argument automatically.

## CRUD routes as opinionated actions

Generated CRUD routes are the bundle default opinion.

At application level you can override specific routes and attach `_resource_action` metadata to drive custom UI behavior (confirmation modal, extra form modal, ordering, runtime support checks).
