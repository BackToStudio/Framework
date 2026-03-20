# Rendering separation

`ConsentBanner` orchestrates the HTML construction but delegates the actual rendering to `ConsentBannerRenderer` (Single Responsibility Principle). The renderer handles:

- Checkbox markup for each category
- CSS styles for the banner
- JavaScript for consent interaction

This separation allows replacing the visual presentation (custom design, framework-specific markup) without touching the banner logic.
