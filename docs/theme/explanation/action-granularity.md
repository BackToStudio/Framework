# Action granularity

Each cleanup action is a standalone class implementing the `Hooks` interface. They are discovered by Symfony's autowiring and executed at boot. This granularity means:

- Disabling one action does not affect the others.
- Each action is independently testable.
- New cleanup actions can be added without modifying existing code.
