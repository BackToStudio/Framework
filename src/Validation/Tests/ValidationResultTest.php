<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Tests;

use BackTo\Framework\Validation\ValidationResult;
use BackTo\Framework\Validation\Violation;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testValidResultHasNoViolations(): void
    {
        $result = ValidationResult::valid();

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getViolations());
    }

    public function testResultWithViolationsIsInvalid(): void
    {
        $result = new ValidationResult(
            new Violation('name', 'Required.', 'not_blank'),
        );

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getViolations());
    }

    public function testMergeWithValidResult(): void
    {
        $a = new ValidationResult(new Violation('a', 'err', 'test'));
        $b = ValidationResult::valid();

        $merged = $a->merge($b);

        $this->assertCount(1, $merged->getViolations());
    }

    public function testMergeCombinesViolations(): void
    {
        $a = new ValidationResult(new Violation('a', 'err1', 'test'));
        $b = new ValidationResult(new Violation('b', 'err2', 'test'));

        $merged = $a->merge($b);

        $this->assertCount(2, $merged->getViolations());
    }

    public function testGetViolationsForField(): void
    {
        $result = new ValidationResult(
            new Violation('name', 'Required.', 'not_blank'),
            new Violation('email', 'Invalid.', 'email'),
            new Violation('name', 'Too short.', 'length'),
        );

        $nameViolations = $result->getViolationsFor('name');
        $this->assertCount(2, $nameViolations);

        $emailViolations = $result->getViolationsFor('email');
        $this->assertCount(1, $emailViolations);
    }

    public function testToArray(): void
    {
        $result = new ValidationResult(
            new Violation('name', 'Required.', 'not_blank'),
        );

        $array = $result->toArray();

        $this->assertSame([
            [
                'field' => 'name',
                'message' => 'Required.',
                'constraint' => 'not_blank',
            ],
        ], $array);
    }
}
