# Blocks

Registers WordPress block styles.

## Classes

| Class | Role |
|-------|------|
| `CustomBlockStyle` | Abstract base for block style definitions |
| `BlockStyleRegistry` | Collects registered block styles |
| `BlockRegistry` | Collects registered blocks |
| `RegisterBlockStyles` | Application orchestrator (hooks into `after_setup_theme`) |
| `Contracts\BlockStyleRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressBlockStyleRegistrar` | WP adapter |
| `Actions\ReplaceImgBlockBySvgBlock` | Replaces `<img>` with inline SVG in image blocks |

## Contracts

### `BlockInterface`

```php
interface BlockInterface
{
    public function getName(): string;
}
```

### `BlockStyleInterface`

```php
interface BlockStyleInterface
{
    public function getProperties(): array;
    public function getLabel(): string;
    public function getStyleName(): string;
    public function getBlocks(): array;
}
```

### `BlockStyleRegistrarInterface`

```php
interface BlockStyleRegistrarInterface
{
    public function register(string $blockName, array $styleProperties): void;
}
```
