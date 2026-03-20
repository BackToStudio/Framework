# Admin Bundle — Architecture & Design

*Explanation — Understanding-oriented*

---

## Hexagonal architecture

The Admin bundle follows the ports & adapters pattern used throughout the framework. The interfaces `AdminPageRegistrarInterface` and `CapabilityManagerInterface` are **ports** — domain contracts that define what the bundle needs from the outside world. The classes in `Infrastructure/` (`WordPressAdminPageRegistrar`, `WordPressCapabilityManager`) are **adapters** that call WordPress functions directly.

This separation allows testing business logic without loading WordPress. In tests, the ports are replaced by mocks or stubs.

---

## Auto-configuration flow

1. `AdminExtension` registers the tag `wordpress.admin_page` for every class implementing `AdminPageInterface`.
2. `RegisterAdminPagePass` (a Symfony compiler pass) collects all tagged services and injects them into `AdminPageRegistry`.
3. At runtime, `RegisterAdminPage` hooks into `admin_menu` and iterates over the registry to create each page via `AdminPageRegistrarInterface`.

This means developers only need to create a class implementing the interface — registration happens automatically.

---

## Separation of responsibilities

The bundle splits concerns across three roles:

- **`AdminPageInterface`** — the contract. Each admin page is a self-contained class defining its title, slug, capability, and render callback.
- **`AdminPageRegistry`** — the collector (Registry pattern). It holds all discovered page instances without knowing how they will be used.
- **`RegisterAdminPage`** — the orchestrator (Hook/Action pattern). It connects the registry to WordPress at the right moment (`admin_menu` hook).

Each admin page class is autonomous and unit-testable, with no direct coupling to WordPress functions.

---

## Why separate AddMenuForEditors and AddReusableBlockMenu?

These are opt-in actions, not core to the admin page registration flow. They address specific real-world needs (editor access, reusable blocks navigation) and are implemented as standalone hook classes. Enabling or disabling them is a matter of including or excluding the service — no configuration flags or conditionals needed.
