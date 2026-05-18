<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Tests;

use BackTo\Framework\Validation\Constraint\Choice;
use BackTo\Framework\Validation\Constraint\Count;
use BackTo\Framework\Validation\Constraint\Each;
use BackTo\Framework\Validation\Constraint\Email;
use BackTo\Framework\Validation\Constraint\Length;
use BackTo\Framework\Validation\Constraint\NotBlank;
use BackTo\Framework\Validation\Constraint\NotNull;
use BackTo\Framework\Validation\Constraint\Range;
use BackTo\Framework\Validation\Constraint\Regex;
use BackTo\Framework\Validation\Constraint\Type;
use BackTo\Framework\Validation\Constraint\Url;
use BackTo\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidDataReturnsValidResult(): void
    {
        $result = $this->validator->validate(
            ['name' => 'John', 'email' => 'john@example.com'],
            [
                'name' => new NotBlank(),
                'email' => [new NotBlank(), new Email()],
            ],
        );

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getViolations());
    }

    public function testMissingFieldReportsViolation(): void
    {
        $result = $this->validator->validate(
            [],
            ['name' => new NotBlank()],
        );

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getViolations());
        $this->assertSame('name', $result->getViolations()[0]->getField());
        $this->assertSame('not_blank', $result->getViolations()[0]->getConstraint());
    }

    public function testMultipleConstraintsOnSameField(): void
    {
        $result = $this->validator->validate(
            ['email' => 'not-an-email'],
            ['email' => [new NotBlank(), new Email()]],
        );

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getViolations());
        $this->assertSame('email', $result->getViolations()[0]->getConstraint());
    }

    public function testMultipleFieldsWithErrors(): void
    {
        $result = $this->validator->validate(
            ['name' => '', 'email' => 'bad'],
            [
                'name' => new NotBlank(),
                'email' => new Email(),
            ],
        );

        $this->assertFalse($result->isValid());
        $this->assertCount(2, $result->getViolations());
    }

    public function testValidateValueWithSingleConstraint(): void
    {
        $result = $this->validator->validateValue('', new NotBlank());

        $this->assertFalse($result->isValid());
    }

    public function testValidateValueWithArrayOfConstraints(): void
    {
        $result = $this->validator->validateValue('ab', [new NotBlank(), new Length(min: 5)]);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getViolations());
        $this->assertSame('length', $result->getViolations()[0]->getConstraint());
    }

    public function testValidateValueValid(): void
    {
        $result = $this->validator->validateValue('hello@test.com', [new NotBlank(), new Email()]);

        $this->assertTrue($result->isValid());
    }

    public function testToArrayFormatsViolations(): void
    {
        $result = $this->validator->validate(
            ['name' => ''],
            ['name' => new NotBlank()],
        );

        $array = $result->toArray();
        $this->assertCount(1, $array);
        $this->assertSame('name', $array[0]['field']);
        $this->assertSame('not_blank', $array[0]['constraint']);
        $this->assertArrayHasKey('message', $array[0]);
    }

    public function testGetViolationsForField(): void
    {
        $result = $this->validator->validate(
            ['a' => '', 'b' => 'not-email'],
            [
                'a' => new NotBlank(),
                'b' => new Email(),
            ],
        );

        $this->assertCount(1, $result->getViolationsFor('a'));
        $this->assertCount(1, $result->getViolationsFor('b'));
        $this->assertCount(0, $result->getViolationsFor('c'));
    }
}
