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
