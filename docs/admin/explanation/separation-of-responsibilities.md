# Separation of responsibilities

The bundle splits concerns across three roles:

- **`AdminPageInterface`** — the contract. Each admin page is a self-contained class defining its title, slug, capability, and render callback.
- **`AdminPageRegistry`** — the collector (Registry pattern). It holds all discovered page instances without knowing how they will be used.
- **`RegisterAdminPage`** — the orchestrator (Hook/Action pattern). It connects the registry to WordPress at the right moment (`admin_menu` hook).

Each admin page class is autonomous and unit-testable, with no direct coupling to WordPress functions.
