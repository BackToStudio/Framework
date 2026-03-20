# Add Subresource Integrity (SRI) hashes to external assets

Protect against CDN supply-chain attacks by verifying the integrity of external scripts and styles:

```php
<?php

use BackTo\Framework\Bundle\Security\Contracts\SubresourceIntegrityInterface;

$sri = $container->get(SubresourceIntegrityInterface::class);

// Register hashes for CDN assets (use sha256, sha384, or sha512)
$sri->registerHash('jquery', 'sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8w');
$sri->registerHash('bootstrap-css', 'sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN');
```

The framework automatically injects the `integrity` and `crossorigin="anonymous"` attributes into the `<script>` and `<link>` tags when WordPress renders them.

### Generate an SRI hash

```bash
# From the command line
openssl dgst -sha384 -binary jquery.min.js | openssl base64 -A
# Output: sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/...
```

The hash format must match: `sha(256|384|512)-[A-Za-z0-9+/=]+`. Invalid formats throw `InvalidArgumentException`.
