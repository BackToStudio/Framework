# Unused CSS removal (tree-shaking)

The unused CSS removal feature analyzes the rendered HTML and strips CSS rules from inline `<style>` blocks whose selectors do not match any element on the page. This is implemented across three single-responsibility classes.

### HtmlSelectorExtractor

Scans the HTML markup (with style blocks removed) and builds three lookup maps: classes, IDs, and tag names. Each map uses `array<string, true>` for O(1) lookups.

### SelectorMatcher

Determines if a CSS selector is "used" with deliberately conservative rules:

- **Universal selectors** (`*`, `:root`, `html`, `body`): always kept
- **CSS custom properties** (`--variable`): always kept
- **Comma-separated selectors** (`a, .b, #c`): kept if any sub-selector matches
- **Compound selectors** (`.foo.bar`): all classes must be present
- **Descendant selectors** (`.parent .child`): only the subject (last element) is checked
- **Pseudo-classes and pseudo-elements** (`:hover`, `::before`): stripped before matching

This approach favors keeping a few unnecessary rules over accidentally removing a needed one that would cause a visual defect.

### CssRuleFilter

Iterates through CSS rules and applies `SelectorMatcher` to each selector. All `@-rules` (`@media`, `@supports`, `@keyframes`, `@font-face`, `@import`, `@charset`) are always preserved because removing them could break conditional declarations or nested rules.

CSS parsing uses manual brace-depth tracking rather than regex to correctly handle nested blocks like `@media { .class { ... } }`.

### Execution order

`RemoveUnusedCss` runs at priority 9 on `template_redirect`. `MinifyHtml` runs at the default priority 10. This ensures tree-shaking processes the raw HTML first, then minification compresses the result.
