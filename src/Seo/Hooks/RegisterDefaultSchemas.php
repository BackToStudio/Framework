<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Seo\Schema\Generator\BreadcrumbSchemaGenerator;
use BackTo\Framework\Seo\Schema\Generator\OrganizationSchemaGenerator;
use BackTo\Framework\Seo\Schema\Generator\PostTypeSchemaResolver;
use BackTo\Framework\Seo\Schema\Generator\WebSiteSchemaGenerator;
use BackTo\Framework\Seo\Schema\SchemaManager;

/**
 * Register default schemas (WebSite, Organization, post type schema, Breadcrumb)
 * and fire the 'framework/seo/schema' action for theme customization.
 */
final class RegisterDefaultSchemas implements Hooks
{
    private readonly SchemaManager $schemaManager;

    private readonly HookDispatcherInterface $hookDispatcher;

    private readonly WebSiteSchemaGenerator $webSiteGenerator;

    private readonly OrganizationSchemaGenerator $organizationGenerator;

    private readonly PostTypeSchemaResolver $postTypeResolver;

    private readonly BreadcrumbSchemaGenerator $breadcrumbGenerator;

    public function __construct(
        SchemaManager $schemaManager,
        HookDispatcherInterface $hookDispatcher,
        WebSiteSchemaGenerator $webSiteGenerator,
        OrganizationSchemaGenerator $organizationGenerator,
        PostTypeSchemaResolver $postTypeResolver,
        BreadcrumbSchemaGenerator $breadcrumbGenerator,
    ) {
        $this->schemaManager = $schemaManager;
        $this->hookDispatcher = $hookDispatcher;
        $this->webSiteGenerator = $webSiteGenerator;
        $this->organizationGenerator = $organizationGenerator;
        $this->postTypeResolver = $postTypeResolver;
        $this->breadcrumbGenerator = $breadcrumbGenerator;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('wp', [$this, 'register']);
    }

    public function register(): void
    {
        $this->schemaManager->add($this->webSiteGenerator->generate());
        $this->schemaManager->add($this->organizationGenerator->generate());

        $this->registerPostTypeSchema();

        $breadcrumb = $this->breadcrumbGenerator->generate();

        if ($breadcrumb !== null) {
            $this->schemaManager->add($breadcrumb);
        }

        /**
         * Action fired after default schemas are registered.
         *
         * Allows themes and plugins to add, remove, or modify schemas
         * before they are rendered in the <head>.
         *
         * Usage in theme:
         *   add_action('framework/seo/schema', function (SchemaManager $manager) {
         *       $manager->add(Schema::faqPage()->mainEntity([...]));
         *   });
         *
         * @param SchemaManager $schemaManager The schema manager instance.
         */
        if (\function_exists('do_action')) {
            \do_action('framework/seo/schema', $this->schemaManager);
        }
    }

    private function registerPostTypeSchema(): void
    {
        if (!\function_exists('is_singular') || !\is_singular()) {
            return;
        }

        $post = \function_exists('get_queried_object') ? \get_queried_object() : null;

        if (!$post instanceof \WP_Post) {
            return;
        }

        $schema = $this->postTypeResolver->resolve($post->post_type, $post->ID);

        if ($schema !== null) {
            $this->schemaManager->add($schema);
        }
    }
}
