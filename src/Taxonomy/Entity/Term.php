<?php

namespace Fantassin\Core\WordPress\Taxonomy\Entity;

use Fantassin\Core\WordPress\Compose\HasId;
use Fantassin\Core\WordPress\Compose\HasParentId;
use Fantassin\Core\WordPress\Compose\HasSlug;
use Fantassin\Core\WordPress\Taxonomy\Contracts\TermInterface;

class Term implements TermInterface
{

    use HasId;
    use HasSlug;
    use HasParentId;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $taxonomy = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): TermInterface
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): TermInterface
    {
        $this->description = $description;
        return $this;
    }

    public function getTaxonomy(): string
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(string $taxonomy): TermInterface
    {
        $this->taxonomy = $taxonomy;
        return $this;
    }
}
