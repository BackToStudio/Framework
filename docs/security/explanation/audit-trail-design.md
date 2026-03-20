# Audit trail design

The audit system uses the Repository pattern:

```
SecurityAuditLogger -> AuditLogRepositoryInterface -> WordPressAuditLogRepository
```

Three severity levels (`Info`, `Warning`, `Critical`) are sufficient for security events. Only a curated set of WordPress options (`siteurl`, `home`, `admin_email`, `users_can_register`, `default_role`, `permalink_structure`, `blogdescription`) are monitored to avoid noise from transient updates.

The notification chain flows through a WordPress action: `SecurityAuditLogger` dispatches `backto_security_event`, which `SecurityNotifier` listens to. Critical events trigger email alerts formatted by `SecurityAlertFormatter` and sent through `MailerInterface`.
