# Classes

### `AdminPageRegistry`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Registry collecting `AdminPageInterface` instances. Populated by the compiler pass.

---

### `RegisterAdminPage`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Hooks into `admin_menu`. Iterates over `AdminPageRegistry` and registers each page via `AdminPageRegistrarInterface`.

---

### `AddMenuForEditors`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Grants editors access to the Appearance menu, hides irrelevant submenus (themes, widgets, customizer), and removes the Customizer link from the admin bar.

---

### `AddReusableBlockMenu`

**Namespace:** `BackTo\Framework\Bundle\Admin`

Registers a top-level "Reusable Blocks" menu (`edit.php?post_type=wp_block`) with `dashicons-block-default` at position 30.
