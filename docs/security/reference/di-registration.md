# DI Registration


`SecurityExtension` registers the bundle. Auto-configuration tags all `SecurityRuleInterface` implementations with `wordpress.security_rule`. The `RegisterSecurityRulePass` compiler pass collects tagged services into the `SecurityRuleRegistry`.

```php
$containerBuilder->registerForAutoconfiguration(SecurityRuleInterface::class)
    ->addTag('wordpress.security_rule');
```
