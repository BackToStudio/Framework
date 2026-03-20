# Monitor SQL queries for dangerous patterns

`DatabaseHardening` inspects every SQL query and logs dangerous patterns:

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security->databaseHardeningEnabled(true);
};
```

### Dangerous patterns detected

- `DROP TABLE`, `TRUNCATE TABLE`, `ALTER TABLE`
- `LOAD_FILE()`, `INTO OUTFILE`, `INTO DUMPFILE` (file I/O)
- `UNION SELECT` (data exfiltration)
- `SLEEP()`, `BENCHMARK()` (time-based attacks)

WordPress core queries (`wp-admin/includes/upgrade.php`, `schema.php`, `wp-db.php`) are excluded from detection.

### Debug mode: detect unprepared queries

In development, enable debug mode to catch queries without `$wpdb->prepare()`:

```php
$security = new DatabaseHardening($hookDispatcher, $logger, $requestContext, debugMode: true);
```

Unprepared queries are logged at info level:

```
[INFO] Unprepared SQL query detected {"query":"SELECT * FROM wp_posts WHERE ID = '42'", "hint":"Consider using $wpdb->prepare()"}
```
