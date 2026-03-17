# Assets

Media and SVG utilities.

## Classes

| Class | Role |
|-------|------|
| `SvgFactory` | Loads SVG from ID, URL, or path |
| `ReplaceImgTagBySvgTag` | HTML tag replacement utility |
| `Contracts\FileLocatorInterface` | Port for WP media functions |
| `Infrastructure\WordPressFileLocator` | WP adapter |

## Contracts

### `FileLocatorInterface`

```php
interface FileLocatorInterface
{
    public function getAttachedFile(int $attachmentId): string;
    public function getUploadDir(): array; // {basedir, baseurl}
}
```
