<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BackToVendor\Symfony\Component\DependencyInjection\Compiler;

use BackToVendor\Symfony\Component\DependencyInjection\Alias;
use BackToVendor\Symfony\Component\DependencyInjection\Definition;
/**
 * Represents a node in your service graph.
 *
 * Value is typically a definition, or an alias.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ServiceReferenceGraphNode
{
    private $id;
    private $inEdges = [];
    private $outEdges = [];
    private $value;
    /**
     * @param string $id    The node identifier
     * @param mixed  $value The node value
     */
    public function __construct(string $id, $value)
    {
        $this->id = $id;
        $this->value = $value;
    }
    public function addInEdge(ServiceReferenceGraphEdge $edge)
    {
        $this->inEdges[] = $edge;
    }
    public function addOutEdge(ServiceReferenceGraphEdge $edge)
    {
        $this->outEdges[] = $edge;
    }
    /**
     * Checks if the value of this node is an Alias.
     *
     * @return bool
     */
    public function isAlias()
    {
        return $this->value instanceof Alias;
    }
    /**
     * Checks if the value of this node is a Definition.
     *
     * @return bool
     */
    public function isDefinition()
    {
        return $this->value instanceof Definition;
    }
    /**
     * Returns the identifier.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Returns the in edges.
     *
     * @return ServiceReferenceGraphEdge[]
     */
    public function getInEdges()
    {
        return $this->inEdges;
    }
    /**
     * Returns the out edges.
     *
     * @return ServiceReferenceGraphEdge[]
     */
    public function getOutEdges()
    {
        return $this->outEdges;
    }
    /**
     * Returns the value of this Node.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Clears all edges.
     */
    public function clear()
    {
        $this->inEdges = $this->outEdges = [];
    }
}
