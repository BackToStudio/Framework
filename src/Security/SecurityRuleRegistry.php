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
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @return SecurityRuleInterface[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return string[]
     */
    public function getActiveRuleNames(): array
    {
        return array_map(
            static fn (SecurityRuleInterface $rule): string => $rule->getName(),
            $this->rules
        );
    }
}
