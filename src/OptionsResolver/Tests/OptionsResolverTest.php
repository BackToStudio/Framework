<?php

declare(strict_types=1);

namespace BackTo\Framework\OptionsResolver\Tests;

use BackTo\Framework\OptionsResolver\Contracts\OptionsResolverInterface;
use BackTo\Framework\OptionsResolver\OptionsResolver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\OptionsResolver\OptionsResolver
 */
class OptionsResolverTest extends TestCase
{
    private OptionsResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OptionsResolver();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(OptionsResolverInterface::class, $this->resolver);
    }

    // --- Defaults ---

    public function testResolveWithDefaults(): void
    {
        $this->resolver->setDefaults(['color' => 'red', 'size' => 10]);

        $result = $this->resolver->resolve([]);

        $this->assertSame(['color' => 'red', 'size' => 10], $result);
    }

    public function testResolveOverridesDefaults(): void
    {
        $this->resolver->setDefaults(['color' => 'red', 'size' => 10]);

        $result = $this->resolver->resolve(['color' => 'blue']);

        $this->assertSame('blue', $result['color']);
        $this->assertSame(10, $result['size']);
    }

    public function testSetDefaultSingle(): void
    {
        $this->resolver->setDefault('color', 'red');

        $result = $this->resolver->resolve([]);

        $this->assertSame(['color' => 'red'], $result);
    }

    // --- Required ---

    public function testRequiredOptionPresent(): void
    {
        $this->resolver->setRequired(['name']);
        $this->resolver->setDefault('name', null);

        $result = $this->resolver->resolve(['name' => 'Alice']);

        $this->assertSame(['name' => 'Alice'], $result);
    }

    public function testRequiredOptionMissingThrows(): void
    {
        $this->resolver->setRequired(['name']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"name"');

        $this->resolver->resolve([]);
    }

    public function testRequiredOptionWithDefaultPasses(): void
    {
        $this->resolver->setRequired(['name']);
        $this->resolver->setDefault('name', 'default');

        $result = $this->resolver->resolve([]);

        $this->assertSame(['name' => 'default'], $result);
    }

    public function testMultipleRequiredMissing(): void
    {
        $this->resolver->setRequired(['a', 'b']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"a"');

        $this->resolver->resolve([]);
    }

    // --- Unknown options ---

    public function testUnknownOptionThrows(): void
    {
        $this->resolver->setDefault('color', 'red');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"size"');

        $this->resolver->resolve(['size' => 10]);
    }

    // --- Allowed types ---

    public function testAllowedTypeValid(): void
    {
        $this->resolver->setDefault('port', 80);
        $this->resolver->setAllowedTypes('port', 'int');

        $result = $this->resolver->resolve(['port' => 8080]);

        $this->assertSame(['port' => 8080], $result);
    }

    public function testAllowedTypeInvalid(): void
    {
        $this->resolver->setDefault('port', 80);
        $this->resolver->setAllowedTypes('port', 'int');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"port"');

        $this->resolver->resolve(['port' => 'not a number']);
    }

    public function testAllowedTypeMultiple(): void
    {
        $this->resolver->setDefault('value', null);
        $this->resolver->setAllowedTypes('value', ['string', 'null']);

        $this->assertSame(['value' => null], $this->resolver->resolve([]));
        $this->assertSame(['value' => 'hello'], $this->resolver->resolve(['value' => 'hello']));
    }

    public function testAllowedTypeBool(): void
    {
        $this->resolver->setDefault('enabled', true);
        $this->resolver->setAllowedTypes('enabled', 'bool');

        $this->assertSame(['enabled' => false], $this->resolver->resolve(['enabled' => false]));
    }

    public function testAllowedTypeFloat(): void
    {
        $this->resolver->setDefault('rate', 1.0);
        $this->resolver->setAllowedTypes('rate', 'float');

        $this->assertSame(['rate' => 2.5], $this->resolver->resolve(['rate' => 2.5]));
    }

    public function testAllowedTypeArray(): void
    {
        $this->resolver->setDefault('items', []);
        $this->resolver->setAllowedTypes('items', 'array');

        $this->assertSame(['items' => [1, 2]], $this->resolver->resolve(['items' => [1, 2]]));
    }

    public function testAllowedTypeClassInstance(): void
    {
        $this->resolver->setDefault('date', new \DateTimeImmutable());
        $this->resolver->setAllowedTypes('date', \DateTimeInterface::class);

        $date = new \DateTimeImmutable('2024-01-01');
        $result = $this->resolver->resolve(['date' => $date]);

        $this->assertSame($date, $result['date']);
    }

    // --- Allowed values ---

    public function testAllowedValuesValid(): void
    {
        $this->resolver->setDefault('strategy', 'transient');
        $this->resolver->setAllowedValues('strategy', ['transient', 'redis', 'filesystem', 'memory']);

        $result = $this->resolver->resolve(['strategy' => 'redis']);

        $this->assertSame(['strategy' => 'redis'], $result);
    }

    public function testAllowedValuesInvalid(): void
    {
        $this->resolver->setDefault('strategy', 'transient');
        $this->resolver->setAllowedValues('strategy', ['transient', 'redis']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"strategy"');

        $this->resolver->resolve(['strategy' => 'memcached']);
    }

    public function testAllowedValuesEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        $this->resolver->setAllowedValues('strategy', []);
    }

    // --- Normalizers ---

    public function testNormalizerIsApplied(): void
    {
        $this->resolver->setDefault('host', 'localhost');
        $this->resolver->setNormalizer('host', fn (string $value): string => strtolower(trim($value)));

        $result = $this->resolver->resolve(['host' => '  MyHost  ']);

        $this->assertSame(['host' => 'myhost'], $result);
    }

    public function testNormalizerRunsAfterValidation(): void
    {
        $this->resolver->setDefault('port', 80);
        $this->resolver->setAllowedTypes('port', 'int');
        $this->resolver->setNormalizer('port', fn (int $v): int => max(1, $v));

        // Type check happens first, normalizer applies after
        $result = $this->resolver->resolve(['port' => 0]);
        $this->assertSame(['port' => 1], $result);
    }

    // --- Validators ---

    public function testValidatorPasses(): void
    {
        $this->resolver->setDefault('ttl', 3600);
        $this->resolver->setValidator('ttl', fn (int $v): bool => $v >= 0);

        $result = $this->resolver->resolve(['ttl' => 0]);

        $this->assertSame(['ttl' => 0], $result);
    }

    public function testValidatorFails(): void
    {
        $this->resolver->setDefault('ttl', 3600);
        $this->resolver->setValidator('ttl', fn (int $v): bool => $v >= 0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "ttl" failed validation');

        $this->resolver->resolve(['ttl' => -1]);
    }

    // --- isDefined / isRequired ---

    public function testIsDefined(): void
    {
        $this->assertFalse($this->resolver->isDefined('color'));

        $this->resolver->setDefault('color', 'red');
        $this->assertTrue($this->resolver->isDefined('color'));
    }

    public function testIsRequired(): void
    {
        $this->assertFalse($this->resolver->isRequired('name'));

        $this->resolver->setRequired(['name']);
        $this->assertTrue($this->resolver->isRequired('name'));
    }

    public function testIsDefinedViaSetRequired(): void
    {
        $this->resolver->setRequired(['name']);
        $this->assertTrue($this->resolver->isDefined('name'));
    }

    public function testIsDefinedViaSetAllowedTypes(): void
    {
        $this->resolver->setAllowedTypes('port', 'int');
        $this->assertTrue($this->resolver->isDefined('port'));
    }

    // --- Edge cases ---

    public function testResolveEmptySchema(): void
    {
        $result = $this->resolver->resolve([]);
        $this->assertSame([], $result);
    }

    public function testSetDefaultEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->setDefault('', 'value');
    }

    public function testSetRequiredEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->setRequired(['']);
    }

    public function testSetAllowedTypesEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->setAllowedTypes('', 'int');
    }

    public function testSetAllowedValuesEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->setAllowedValues('', ['a']);
    }

    public function testSetNormalizerEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->setNormalizer('', fn ($v) => $v);
    }

    public function testSetValidatorEmptyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->setValidator('', fn ($v) => true);
    }

    public function testFluentApi(): void
    {
        $result = $this->resolver
            ->setDefault('a', 1)
            ->setDefaults(['b' => 2])
            ->setRequired(['a'])
            ->setAllowedTypes('a', 'int')
            ->setAllowedTypes('b', 'int')
            ->resolve(['a' => 10]);

        $this->assertSame(['a' => 10, 'b' => 2], $result);
    }

    public function testDefaultNullWithType(): void
    {
        $this->resolver->setDefault('value', null);
        $this->resolver->setAllowedTypes('value', ['string', 'null']);

        $this->assertSame(['value' => null], $this->resolver->resolve([]));
        $this->assertSame(['value' => 'test'], $this->resolver->resolve(['value' => 'test']));
    }

    public function testCombinedTypesAndValues(): void
    {
        $this->resolver->setDefault('level', 'info');
        $this->resolver->setAllowedTypes('level', 'string');
        $this->resolver->setAllowedValues('level', ['debug', 'info', 'warning', 'error']);

        $result = $this->resolver->resolve(['level' => 'debug']);
        $this->assertSame(['level' => 'debug'], $result);
    }

    public function testCombinedTypesAndValuesTypeFailsFirst(): void
    {
        $this->resolver->setDefault('level', 'info');
        $this->resolver->setAllowedTypes('level', 'string');
        $this->resolver->setAllowedValues('level', ['debug', 'info']);

        $this->expectException(\InvalidArgumentException::class);

        $this->resolver->resolve(['level' => 42]);
    }
}
