# Use WP-CLI Scaffolding

The framework provides WP-CLI commands to generate boilerplate classes for all modules.

## Setup

In your plugin or theme, load the CLI bootstrap:

```php
// functions.php or plugin main file
if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__ . '/vendor/backto/framework/src/Cli/cli-bootstrap.php';
}
```

## Available commands

### Post Type

```bash
wp make:post-type Event --namespace=App\\PostType --dir=src/PostType
```

Generates a class implementing `PostTypeInterface`.

### Taxonomy

```bash
wp make:taxonomy EventCategory --namespace=App\\Taxonomy --dir=src/Taxonomy --post-types=event,post
```

Generates a class implementing `TaxonomyInterface` with associated post types.

### Block

```bash
wp make:block Hero --namespace=App\\Block --dir=src/Block --block-namespace=mytheme
```

Generates a class implementing `BlockInterface`. Block name: `mytheme/hero`.

### Hook

```bash
wp make:hook RegisterSidebars --namespace=App\\Hook --dir=src/Hook
```

Generates a class implementing `Hooks` with `HookDispatcherInterface` injection.

### REST Route

```bash
wp make:rest-route GetEvents --namespace=App\\RestApi --dir=src/RestApi --route-namespace=myapp/v1
```

Generates a class implementing `RestRouteInterface`.

## Common options

| Option | Description | Default |
|--------|-------------|---------|
| `--namespace` | PHP namespace | `App` |
| `--dir` | Output directory | `.` |
| `--force` | Overwrite existing file | `false` |

## Input validation

- Names must be alphanumeric (letters, numbers, hyphens, underscores, spaces)
- Namespaces must be valid PHP namespaces
- Post types must match `[a-z0-9_-]{1,20}`
- Block namespaces must be lowercase alphanumeric
- Route namespaces must follow `namespace/version` format
