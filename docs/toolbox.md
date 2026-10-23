# Toolbox entities

The bundle can expose optional toolbox entities/repositories to the host project through Doctrine ORM mappings.

## Feature flags

Configuration root: `web_develovers_resource`.

```yaml
# config/packages/web_develovers_resource.yaml
web_develovers_resource:
  toolbox:
    auditing: true   # auditing registra le operazioni nel sistema
    activity: true    # includes Activity + ActivityType
    attachment: true
    bookmark: true
    follower: true
    timeline: true
```

All flags are enabled by default.

`auditing` e `timeline` sono concetti distinti:
- `auditing`: traccia e registra le operazioni di sistema.
- `timeline`: rappresenta la parte di visualizzazione per mostrare gli eventi all’utente.

Quando `auditing` è abilitato, il mapping Doctrine delle `TimelineEntry` viene registrato anche se `timeline` è disabilitato, così la raccolta audit può essere persistita.

When a flag is enabled, the bundle prepends the related Doctrine mapping and therefore Doctrine can generate/create the related table(s) (migration/schema update).
When a flag is disabled, that feature mapping is not registered.

## CRUD layout and toolbox rendering

You can configure the CRUD layout for each screen (`show`, `edit`, `create`) from the same root.

```yaml
web_develovers_resource:
  crud:
    show:
      toolbox_mode: enabled    # enabled | offcanvas | disabled
      main_columns: 8          # Bootstrap col-xl-<N>
      toolbox_columns: 4       # Bootstrap col-xl-<N>
      offcanvas_width: 560     # px, usato quando toolbox_mode=offcanvas
    edit:
      toolbox_mode: enabled    # enabled | offcanvas | disabled
      main_columns: 8
      toolbox_columns: 4
      offcanvas_width: 560
    create:
      toolbox_mode: disabled   # enabled | offcanvas | disabled
      main_columns: 8
      toolbox_columns: 4
      offcanvas_width: 560
```

### `toolbox_mode`

- `enabled`: toolbox rendered as desktop sidebar (`col-xl-*`).
- `offcanvas`: toolbox rendered inside a Bootstrap offcanvas panel, opened by a `⚙ Toolbox` trigger in the top-right area of the page header.
  In this mode, main content is always full width (`col-12`) regardless of `main_columns`/`toolbox_columns`.
  The panel closes only via top-right dismiss (`X`) or click outside the panel (backdrop), not with `ESC`.
- `disabled`: toolbox is not rendered.

### Columns (`main_columns`, `toolbox_columns`)

- Both values are integers between `1` and `12`.
- They control `col-xl-*` classes for the main content and toolbox areas.
- Defaults preserve current behavior (`8` + `4`) for `show` and `edit`.
- `create` defaults to `toolbox_mode: disabled` (no toolbox shown unless explicitly enabled).

### Offcanvas width (`offcanvas_width`)

- Integer in pixels, between `320` and `1200`.
- Default: `560` for `show`, `edit`, and `create`.
- Applied only when `toolbox_mode: offcanvas`.
