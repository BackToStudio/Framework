<?php

namespace Fantassin\Core\WordPress\Taxonomy;

interface TaxonomyInterface
{

    /**
     * @return string
     */
    public function getKey(): ?string;

    /**
     * @return array
     */
    public function getArgs(): array;

    /**
     * @return array
     */
    public function getPostTypes(): array;
}
