# Configure the Heartbeat API

### Disable Heartbeat on the frontend

```php
$performance->heartbeatDisableFrontend(true); // enabled by default
```

### Change the admin interval

The admin Heartbeat interval defaults to 60 seconds (WordPress default is 15):

```php
$performance->heartbeatAdminInterval(120); // 120 seconds
```
