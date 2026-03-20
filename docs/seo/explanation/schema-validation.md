# Schema validation

Each schema type declares its Google-required properties via the protected `getRequiredProperties()` method. For example, `Product` requires `name`, `image`, and `offers`.

Validation is optional and non-blocking: an incomplete schema is still rendered as JSON-LD. The `validate()` method returns missing property names, allowing developers to check conformance during development. The `SchemaManager` provides global validation via `validate()`, returning `[type => [missing properties]]` for all registered schemas.
