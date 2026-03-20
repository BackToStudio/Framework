# CacheableRequestChecker design

The checker applies an elimination cascade to determine if a request should be cached:

1. **Admin page** — not cacheable (personalized UI)
2. **Non-GET method** — not cacheable (state-changing requests)
3. **Logged-in user** — not cacheable (personalized content like admin bar)
4. **Query parameters** — not cacheable (variable output)
5. **Excluded URL prefix** — not cacheable (WordPress internals)

The order is deliberate: cheap checks (admin flag, HTTP method) run first. The logged-in check is more expensive because it reads session cookies.
