# Sanitize user input safely

Use `InputSanitizerInterface` for context-aware input cleaning:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\InputSanitizerInterface;

class ContactFormHandler
{
    public function __construct(
        private readonly InputSanitizerInterface $sanitizer,
    ) {}

    public function process(array $data): array
    {
        return [
            'name'    => $this->sanitizer->sanitizeText($data['name'] ?? ''),
            'email'   => $this->sanitizer->sanitizeEmail($data['email'] ?? ''),
            'website' => $this->sanitizer->sanitizeUrl($data['website'] ?? ''),
            'message' => $this->sanitizer->sanitizeTextarea($data['message'] ?? ''),
            'file'    => $this->sanitizer->sanitizeFileName($data['file'] ?? ''),
        ];
    }
}
```

### Sanitize HTML with specific allowed tags

```php
$cleanHtml = $this->sanitizer->sanitizeHtml($rawHtml, [
    'p'      => [],
    'a'      => ['href' => true, 'title' => true],
    'strong'  => [],
    'em'     => [],
]);

// Empty $allowedHtml array falls back to wp_kses_post (post content defaults)
$postContent = $this->sanitizer->sanitizeHtml($rawContent);
```
