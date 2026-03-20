# Why separate AddMenuForEditors and AddReusableBlockMenu?


These are opt-in actions, not core to the admin page registration flow. They address specific real-world needs (editor access, reusable blocks navigation) and are implemented as standalone hook classes. Enabling or disabling them is a matter of including or excluding the service — no configuration flags or conditionals needed.
