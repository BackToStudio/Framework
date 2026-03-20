# Limit post revisions

```php
$performance->revisionsLimit(3); // keep at most 3 revisions per post
```

The default is 5. This applies the `wp_revisions_to_keep` filter.
