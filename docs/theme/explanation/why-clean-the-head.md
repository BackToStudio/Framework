# Why clean the `<head>`?

WordPress injects numerous tags into the HTML `<head>` by default: RSS feed links, RSD (XML-RPC) links, Windows Live Writer manifest, emoji scripts and styles, version meta tags, SVG filters, and more. Most of these are unnecessary for a modern site and create problems:

- **Performance** — Emoji scripts and styles add HTTP requests and blocking JavaScript on every page load.
- **Security** — The WordPress version meta tag reveals the exact version to attackers scanning for known vulnerabilities.
- **HTML pollution** — Relational links, SVG filters, and feed links bloat the document with unused markup.

The bundle applies a **secure by default** approach: all cleanup actions are enabled automatically. Developers opt out of specific actions rather than opting in.
