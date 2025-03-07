<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BackToVendor\Symfony\Component\DependencyInjection;

use BackToVendor\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use BackToVendor\Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
/**
 * This definition extends another definition.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ChildDefinition extends Definition
{
    private $parent;
    /**
     * @param string $parent The id of Definition instance to decorate
     */
    public function __construct(string $parent)
    {
        $this->parent = $parent;
    }
    /**
     * Returns the Definition to inherit from.
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }
    /**
     * Sets the Definition to inherit from.
     *
     * @return $this
     */
    public function setParent(string $parent)
    {
        $this->parent = $parent;
        return $this;
    }
    /**
     * Gets an argument to pass to the service constructor/factory method.
     *
     * If replaceArgument() has been used to replace an argument, this method
     * will return the replacement value.
     *
     * @param int|string $index
     *
     * @return mixed
     *
     * @throws OutOfBoundsException When the argument does not exist
     */
    public function getArgument($index)
    {
        if (\array_key_exists('index_' . $index, $this->arguments)) {
            return $this->arguments['index_' . $index];
        }
        return parent::getArgument($index);
    }
    /**
     * You should always use this method when overwriting existing arguments
     * of the parent definition.
     *
     * If you directly call setArguments() keep in mind that you must follow
     * certain conventions when you want to overwrite the arguments of the
     * parent definition, otherwise your arguments will only be appended.
     *
     * @param int|string $index
     * @param mixed      $value
     *
     * @return $this
     *
     * @throws InvalidArgumentException when $index isn't an integer
     */
    public function replaceArgument($index, $value)
    {
        if (\is_int($index)) {
            $this->arguments['index_' . $index] = $value;
        } elseif (\str_starts_with($index, '$')) {
            $this->arguments[$index] = $value;
        } else {
            throw new InvalidArgumentException('The argument must be an existing index or the name of a constructor\'s parameter.');
        }
        return $this;
    }
}
