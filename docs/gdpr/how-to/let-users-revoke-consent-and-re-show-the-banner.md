# Let users revoke consent and re-show the banner

The consent banner includes a toggle button that appears after the user has made their choice. It renders automatically via `RegisterGdpr` at `wp_footer` priority 100.

### How it works

1. User accepts/rejects → banner hides, toggle button appears (`#gdpr-consent-toggle`).
2. User clicks toggle → banner re-shows with current selections pre-checked.
3. User updates choices → cookie is updated with new consent values.

### Custom trigger button

If you want a link in your footer instead of the default toggle:

```html
<a href="#" onclick="document.getElementById('gdpr-consent-banner').style.display='flex'; return false;">
    Manage cookie preferences
</a>
```

### Cookie details

- **Name:** `gdpr_consent`
- **Duration:** 365 days
- **Path:** `/` (site-wide)
- **SameSite:** `Lax`
- **Format:** JSON object mapping category keys to booleans

Example cookie value: `{"required":true,"analytics":false,"marketing":true}`
