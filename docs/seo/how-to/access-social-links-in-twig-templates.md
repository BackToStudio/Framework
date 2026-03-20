# Access social links in Twig templates

The bundle injects social links from the active SEO plugin (Yoast or SEOPress) into the Timber context. Available variables: `facebook`, `twitter`, `instagram`, `linkedin`, `pinterest`, `youtube`.

```twig
<nav class="social-links" aria-label="Social media">
    {% if facebook %}
        <a href="{{ facebook }}" target="_blank" rel="noopener">Facebook</a>
    {% endif %}
    {% if twitter %}
        <a href="{{ twitter }}" target="_blank" rel="noopener">Twitter</a>
    {% endif %}
    {% if instagram %}
        <a href="{{ instagram }}" target="_blank" rel="noopener">Instagram</a>
    {% endif %}
</nav>
```

These links are extracted from Yoast's `wpseo_social` option or SEOPress's `seopress_social_option_name` option, without the template needing to know which plugin is installed.
