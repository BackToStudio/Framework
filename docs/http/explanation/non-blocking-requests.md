# Non-blocking requests

WordPress supports non-blocking HTTP via `['blocking' => false]`. The request is dispatched and execution continues immediately. The returned response has a `0` status code and empty body.

This is used internally by `PreloadExecutor` for cache warming: it fires GET requests to the site's own pages without waiting for responses, letting WordPress's page cache populate in the background.
