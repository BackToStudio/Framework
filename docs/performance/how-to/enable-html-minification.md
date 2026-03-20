# Enable HTML minification

HTML minification is disabled by default. Enable it explicitly:

```php
$performance->minifyHtml(true);
```

This removes HTML comments, collapses whitespace between block-level tags, minifies inline CSS and JavaScript, and strips redundant `type` attributes. Content inside `<pre>`, `<code>`, and `<textarea>` is preserved.
