<?php

namespace BackTo\Framework\Taxonomy\Entity;

use BackTo\Framework\Compose\HasId;
use BackTo\Framework\Compose\HasParentId;
use BackTo\Framework\Compose\HasSlug;
use BackTo\Framework\Taxonomy\Contracts\TermInterface;

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
