<?php

declare(strict_types=1);

namespace BackTo\Framework\OptionsResolver\Contracts;

/**
 * Resolves an option array against a defined schema.
 *
 * Defines required/optional options, defaults, allowed types, allowed values,
 * normalizers, and validators. Calling resolve() on user-provided options
 * returns a fully validated, normalized, and merged result.
 *
 * Port interface — inject this contract, not the concrete class.
 */
interface OptionsResolverInterface
{
    /**
     * Set default values for options.
     *
     * @param array<string, mixed> $defaults
     * @return static
     */
    public function setDefaults(array $defaults): static;

    /**
     * Set a default value for a single option.
     *
     * @return static
     */
    public function setDefault(string $option, mixed $default): static;

    /**
     * Mark options as required (must be provided by the caller).
     *
     * @param string[] $options
     * @return static
     */
    public function setRequired(array $options): static;

    /**
     * Set allowed types for an option (e.g. 'string', 'int', 'bool', 'array', class names).
     *
     * @param string|string[] $types
     * @return static
     */
    public function setAllowedTypes(string $option, string|array $types): static;

    /**
     * Set allowed values for an option (allowlist).
     *
     * @param array<mixed> $values
     * @return static
     */
    public function setAllowedValues(string $option, array $values): static;

    /**
     * Register a normalizer for an option. Called after validation, before returning.
     *
     * @param callable(mixed): mixed $normalizer
     * @return static
     */
    public function setNormalizer(string $option, callable $normalizer): static;

    /**
     * Register a validator for an option. Must return true or throw.
     *
     * @param callable(mixed): bool $validator
     * @return static
     */
    public function setValidator(string $option, callable $validator): static;

    /**
     * Check if an option is defined (has a default or is required).
     */
    public function isDefined(string $option): bool;

    /**
     * Check if an option is required.
     */
    public function isRequired(string $option): bool;

    /**
     * Resolve user-provided options against the schema.
     *
     * Merges with defaults, validates types and allowed values,
     * applies normalizers, and checks required options.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws \InvalidArgumentException If validation fails.
     */
    public function resolve(array $options): array;
}
