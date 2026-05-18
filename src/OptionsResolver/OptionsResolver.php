<?php

declare(strict_types=1);

namespace BackTo\Framework\OptionsResolver;

use BackTo\Framework\OptionsResolver\Contracts\OptionsResolverInterface;
use BackToVendor\Symfony\Component\OptionsResolver\OptionsResolver as SymfonyOptionsResolver;

/**
 * Resolves and validates option arrays against a declared schema.
 *
 * Adapter around the vendor-scoped Symfony OptionsResolver component.
 * Provides the same fail-fast validation with clear messages through
 * the framework's OptionsResolverInterface port.
 */
final class OptionsResolver implements OptionsResolverInterface
{
    private readonly SymfonyOptionsResolver $resolver;

    /** @var array<string, callable(mixed): bool> */
    private array $validators = [];

    public function __construct()
    {
        $this->resolver = new SymfonyOptionsResolver();
    }

    public function setDefaults(array $defaults): static
    {
        foreach ($defaults as $option => $value) {
            $this->setDefault($option, $value);
        }

        return $this;
    }

    public function setDefault(string $option, mixed $default): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        $this->resolver->setDefault($option, $default);

        return $this;
    }

    public function setRequired(array $options): static
    {
        foreach ($options as $option) {
            if ($option === '') {
                throw new \InvalidArgumentException('Option name must not be empty.');
            }
        }

        $this->resolver->setRequired($options);

        return $this;
    }

    public function setAllowedTypes(string $option, string|array $types): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        $this->resolver->setDefined($option);
        $this->resolver->setAllowedTypes($option, $types);

        return $this;
    }

    public function setAllowedValues(string $option, array $values): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        if ($values === []) {
            throw new \InvalidArgumentException(sprintf('Allowed values for option "%s" must not be empty.', $option));
        }

        $this->resolver->setDefined($option);
        $this->resolver->setAllowedValues($option, $values);

        return $this;
    }

    public function setNormalizer(string $option, callable $normalizer): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        $this->resolver->setDefined($option);
        $this->resolver->setNormalizer(
            $option,
            static function ($options, $value) use ($normalizer) {
                return $normalizer($value);
            }
        );

        return $this;
    }

    public function setValidator(string $option, callable $validator): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        $this->validators[$option] = $validator;
        $this->resolver->setDefined($option);

        return $this;
    }

    public function isDefined(string $option): bool
    {
        return $this->resolver->isDefined($option);
    }

    public function isRequired(string $option): bool
    {
        return $this->resolver->isRequired($option);
    }

    public function resolve(array $options): array
    {
        $resolved = $this->resolver->resolve($options);
        $this->runValidators($resolved);

        return $resolved;
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private function runValidators(array $resolved): void
    {
        foreach ($this->validators as $option => $validator) {
            if (!array_key_exists($option, $resolved)) {
                continue;
            }

            $result = $validator($resolved[$option]);

            if ($result !== true) {
                throw new \InvalidArgumentException(sprintf(
                    'Option "%s" failed validation.',
                    $option
                ));
            }
        }
    }
}
