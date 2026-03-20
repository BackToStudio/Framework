# Why PSR-18 specifically?

PSR-18 (`Psr\Http\Client\ClientInterface`) is the PHP industry standard for HTTP clients. By extending it rather than creating a proprietary interface, the framework gains:

1. **Interoperability** — Any library that accepts a PSR-18 client works out of the box (SDKs, API wrappers, monitoring tools)
2. **Familiarity** — Developers who know Guzzle or Symfony HttpClient recognize `sendRequest(RequestInterface): ResponseInterface` instantly
3. **Swappability** — The WordPress adapter can be replaced by Guzzle, Symfony HttpClient, or a test double without changing consumer code
