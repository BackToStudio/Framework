<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Seo\Schema\Generator\ArticleSchemaGenerator;
use BackTo\Framework\Seo\Schema\Generator\BreadcrumbSchemaGenerator;
use BackTo\Framework\Seo\Schema\Generator\OrganizationSchemaGenerator;
use BackTo\Framework\Seo\Schema\Generator\WebSiteSchemaGenerator;
use BackTo\Framework\Seo\Schema\SchemaManager;

/**
 * Register default schemas (WebSite, Organization, Article, Breadcrumb)
 * and apply the 'framework/seo/schema' filter for theme customization.
 */
class RegisterDefaultSchemas implements Hooks
{
    private SchemaManager $schemaManager;

    private HookDispatcherInterface $hookDispatcher;

    private WebSiteSchemaGenerator $webSiteGenerator;

    private OrganizationSchemaGenerator $organizationGenerator;

    private ArticleSchemaGenerator $articleGenerator;

    private BreadcrumbSchemaGenerator $breadcrumbGenerator;

    public function __construct(
        SchemaManager $schemaManager,
        HookDispatcherInterface $hookDispatcher,
        WebSiteSchemaGenerator $webSiteGenerator,
        OrganizationSchemaGenerator $organizationGenerator,
        ArticleSchemaGenerator $articleGenerator,
        BreadcrumbSchemaGenerator $breadcrumbGenerator,
    ) {
        $this->schemaManager = $schemaManager;
        $this->hookDispatcher = $hookDispatcher;
        $this->webSiteGenerator = $webSiteGenerator;
        $this->organizationGenerator = $organizationGenerator;
        $this->articleGenerator = $articleGenerator;
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

        $article = $this->articleGenerator->generate();

        if ($article !== null) {
            $this->schemaManager->add($article);
        }

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
}
