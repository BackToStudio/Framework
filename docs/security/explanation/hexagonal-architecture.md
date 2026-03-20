# Hexagonal architecture

The bundle follows the **ports and adapters** pattern used throughout the framework. Security rules depend on port interfaces (in `Contracts/`), never on WordPress functions directly.

**Ports** define what the domain needs: throttle tracking, audit storage, nonce management, mail delivery, IP resolution. **Adapters** (in `Infrastructure/`) implement those ports using WordPress APIs: transients, `wp_options`, `user_meta`, `wp_mail`, and `wp_create_nonce`.

This separation means:

- **Testability** — Unit tests mock the port interfaces. No WordPress bootstrap required.
- **Substitutability** — Swapping storage (e.g., Redis instead of transients) means writing one adapter, not changing any rule.
- **Clarity** — Reading a rule's constructor signature tells you exactly what it depends on.
