# Why a performance bundle in a WordPress framework?

WordPress loads its full stack on every request: database connections, theme files, plugin hooks, and template rendering. For anonymous visitors viewing published content, most of this work produces identical output. The Performance bundle short-circuits this by caching the rendered HTML and serving it before WordPress boots.

Beyond caching, WordPress ships with features that most sites never use (emoji scripts, oEmbed, XML-RPC, Heartbeat on the frontend). Each adds kilobytes of JavaScript and CSS, plus server-side overhead. The bundle removes these by default, opting for a "clean slate" approach where developers explicitly re-enable what they need.
