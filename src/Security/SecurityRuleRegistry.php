<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class SecurityRuleRegistry implements RegistryInterface
{
    /** @var SecurityRuleInterface[] */
    private array $rules = [];

    public function add(SecurityRuleInterface $rule): self
    {
        $name = $rule->getName();

        if (isset($this->rules[$name])) {
            return $this;
        }

        $this->rules[$name] = $rule;

        return $this;
    }

    /**
     * @return SecurityRuleInterface[]
     */
    public function getRules(): array
    {
        return array_values($this->rules);
    }

    public function has(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * @return string[]
     */
    public function getActiveRuleNames(): array
    {
        return array_keys($this->rules);
    }
}
