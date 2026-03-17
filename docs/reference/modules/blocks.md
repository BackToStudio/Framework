# Blocks

Registers WordPress block styles.

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
