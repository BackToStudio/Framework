# Why presets?


Analytics and marketing tools share common integration patterns (inline vs external, head vs footer, priority ordering). The preset classes (`GoogleAnalyticsScript`, `GoogleTagManagerScript`, `HotjarScript`, etc.) encapsulate these patterns so developers only need to provide their tracking ID. Each preset extends `TrackingScript` with the correct defaults for its service.
