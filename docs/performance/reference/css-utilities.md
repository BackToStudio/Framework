# CSS utilities

**Namespace:** `BackTo\Framework\Bundle\Performance\Css`

| Class | Method | Description |
|---|---|---|
| `HtmlSelectorExtractor` | `extract(string $markup): array` | Returns `{classes, ids, tags}` as `array<string, true>` maps |
| `CssRuleFilter` | `filter(string $css, array $selectors): string` | Keeps only rules whose selectors match. Always keeps `@-rules` |
| `SelectorMatcher` | `isSelectorUsed(string $selector, array $selectors): bool` | `true` if selector matches page elements |
