<?php

declare(strict_types=1);

namespace BackTo\Framework\OptionsResolver;

use BackTo\Framework\OptionsResolver\Contracts\OptionsResolverInterface;

/**
 * Resolves and validates option arrays against a declared schema.
 *
 * Inspired by Symfony's OptionsResolver, tailored for the BackTo framework.
 * Provides defaults, required options, type checking, allowlists,
 * normalizers, and custom validators — all fail-fast with clear messages.
 */
final class OptionsResolver implements OptionsResolverInterface
{
    /** @var array<string, mixed> */
    private array $defaults = [];

    /** @var array<string, true> */
    private array $required = [];

    /** @var array<string, string[]> */
    private array $allowedTypes = [];

    /** @var array<string, array<mixed>> */
    private array $allowedValues = [];

    /** @var array<string, callable(mixed): mixed> */
    private array $normalizers = [];

    /** @var array<string, callable(mixed): bool> */
    private array $validators = [];

    /** @var array<string, true> All known options (defaults + required) */
    private array $defined = [];

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

        $this->defaults[$option] = $default;
        $this->defined[$option] = true;

        return $this;
    }

    public function setRequired(array $options): static
    {
        foreach ($options as $option) {
            if ($option === '') {
                throw new \InvalidArgumentException('Option name must not be empty.');
            }

            $this->required[$option] = true;
            $this->defined[$option] = true;
        }

        return $this;
    }

    public function setAllowedTypes(string $option, string|array $types): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        $this->allowedTypes[$option] = (array) $types;
        $this->defined[$option] = true;

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

        $this->allowedValues[$option] = $values;
        $this->defined[$option] = true;

        return $this;
    }

    public function setNormalizer(string $option, callable $normalizer): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        $this->normalizers[$option] = $normalizer;
        $this->defined[$option] = true;

        return $this;
    }

    public function setValidator(string $option, callable $validator): static
    {
        if ($option === '') {
            throw new \InvalidArgumentException('Option name must not be empty.');
        }

        $this->validators[$option] = $validator;
        $this->defined[$option] = true;

        return $this;
    }

    public function isDefined(string $option): bool
    {
        return isset($this->defined[$option]);
    }

    public function isRequired(string $option): bool
    {
        return isset($this->required[$option]);
    }

    public function resolve(array $options): array
    {
        $this->checkUndefinedOptions($options);
        $resolved = array_replace($this->defaults, $options);
        $this->checkRequiredOptions($resolved);
        $this->checkAllowedTypes($resolved);
        $this->checkAllowedValues($resolved);
        $this->runValidators($resolved);
        $resolved = $this->applyNormalizers($resolved);

        return $resolved;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function checkUndefinedOptions(array $options): void
    {
        $unknown = array_diff_key($options, $this->defined);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown option(s) "%s". Defined options are: "%s".',
                implode('", "', array_keys($unknown)),
                implode('", "', array_keys($this->defined))
            ));
        }
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private function checkRequiredOptions(array $resolved): void
    {
        $missing = array_diff_key($this->required, $resolved);
        if ($missing !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Required option(s) "%s" missing.',
                implode('", "', array_keys($missing))
            ));
        }
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private function checkAllowedTypes(array $resolved): void
    {
        foreach ($this->allowedTypes as $option => $types) {
            if (!array_key_exists($option, $resolved)) {
                continue;
            }

            $value = $resolved[$option];

            if (!self::matchesType($value, $types)) {
                throw new \InvalidArgumentException(sprintf(
                    'Option "%s" expected type(s) "%s", got "%s".',
                    $option,
                    implode('|', $types),
                    get_debug_type($value)
                ));
            }
        }
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private function checkAllowedValues(array $resolved): void
    {
        foreach ($this->allowedValues as $option => $allowed) {
            if (!array_key_exists($option, $resolved)) {
                continue;
            }

            $value = $resolved[$option];

            if (!in_array($value, $allowed, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Option "%s" value "%s" is not allowed. Allowed: "%s".',
                    $option,
                    self::formatValue($value),
                    implode('", "', array_map([self::class, 'formatValue'], $allowed))
                ));
            }
        }
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

    /**
     * @param array<string, mixed> $resolved
     * @return array<string, mixed>
     */
    private function applyNormalizers(array $resolved): array
    {
        foreach ($this->normalizers as $option => $normalizer) {
            if (array_key_exists($option, $resolved)) {
                $resolved[$option] = $normalizer($resolved[$option]);
            }
        }

        return $resolved;
    }

    /**
     * @param string[] $types
     */
    private static function matchesType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            if (self::valueMatchesType($value, $type)) {
                return true;
            }
        }

        return false;
    }

    private static function valueMatchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'bool', 'boolean' => is_bool($value),
            'int', 'integer' => is_int($value),
            'float', 'double' => is_float($value),
            'string' => is_string($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'callable' => is_callable($value),
            'null' => $value === null,
            'numeric' => is_numeric($value),
            'scalar' => is_scalar($value),
            default => $value instanceof $type,
        };
    }

    private static function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return get_debug_type($value);
    }
}
