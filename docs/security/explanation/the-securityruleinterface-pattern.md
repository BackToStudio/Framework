# The SecurityRuleInterface pattern

### Problem

How do you add new security rules without modifying existing code, while ensuring all rules are activated at boot?

### Solution

The pattern has three parts:

1. **`SecurityRuleInterface`** extends `HookInterface` and adds `getName(): string`. It marks a class as a security rule.

2. **Auto-configuration:** `SecurityExtension` tags every `SecurityRuleInterface` implementation with `wordpress.security_rule` automatically.

3. **`RegisterSecurityRulePass`:** This compiler pass collects all tagged services and injects them into `SecurityRuleRegistry`.

The result is **Open/Closed**: adding a rule means creating a class. No configuration changes, no registration code. The registry also powers `SecurityHealthCheck`, which verifies that critical rules are present at runtime.
