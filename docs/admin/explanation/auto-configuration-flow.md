# Auto-configuration flow

1. `AdminExtension` registers the tag `wordpress.admin_page` for every class implementing `AdminPageInterface`.
2. `RegisterAdminPagePass` (a Symfony compiler pass) collects all tagged services and injects them into `AdminPageRegistry`.
3. At runtime, `RegisterAdminPage` hooks into `admin_menu` and iterates over the registry to create each page via `AdminPageRegistrarInterface`.

This means developers only need to create a class implementing the interface — registration happens automatically.
