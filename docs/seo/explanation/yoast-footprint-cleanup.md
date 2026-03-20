# Yoast footprint cleanup


Yoast SEO injects HTML comments (`<!-- This site is optimized with the Yoast SEO plugin ... -->`) and exposes its version number in the page source. `CleanYoastFootprint` removes these via two Yoast-native filters:

- `wpseo_debug_markers` -- `__return_false`
- `wpseo_hide_version` -- `__return_true`

This runs automatically with no configuration required.
