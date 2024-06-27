<?php

namespace BackTo\Framework\Taxonomy\Contracts;

use BackTo\Framework\Contracts\IdInterface;
use BackTo\Framework\Contracts\ParentIdInterface;
use BackTo\Framework\Contracts\SlugInterface;

interface TermInterface extends IdInterface, SlugInterface, ParentIdInterface
{

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return TermInterface
     */
    public function setName(string $name): TermInterface;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @param string $description
     * @return TermInterface
     */
    public function setDescription(string $description): TermInterface;

    /**
     * @return string
     */
    public function getTaxonomy(): string;

    /**
     * @param string $taxonomy
     * @return TermInterface
     */
    public function setTaxonomy(string $taxonomy): TermInterface;

}
