<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo;

/**
 * Loads SEO configuration from the project's config/seo.php file.
 *
 * Expected format:
 *
 *   return [
 *       'schema' => [
 *           'post_type_map' => [
 *               'post'      => ArticleSchemaGenerator::class,
 *               'formation' => CourseSchemaGenerator::class,
 *           ],
 *           'disable_plugin_schema' => true,
 *       ],
 *   ];
 */
class SeoConfig
{
    /** @var array<string, mixed> */
    private readonly array $config;

    /**
     * @param string $themeDirectory The project/theme root directory
     */
    public function __construct(string $themeDirectory)
    {
        $configFile = rtrim($themeDirectory, '/') . '/config/seo.php';

        if (\file_exists($configFile)) {
            $loaded = require $configFile;
            $this->config = \is_array($loaded) ? $loaded : [];
        } else {
            $this->config = [];
        }
    }

    /**
     * Get the full config array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get a nested config value using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!\is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get the post_type → generator class mapping.
     *
     * @return array<string, class-string>
     */
    public function getPostTypeMap(): array
    {
        $map = $this->get('schema.post_type_map', []);

        return \is_array($map) ? $map : [];
    }

    /**
     * Whether the framework should disable the SEO plugin's native schema output.
     */
    public function shouldDisablePluginSchema(): bool
    {
        return (bool) $this->get('schema.disable_plugin_schema', true);
    }
}
