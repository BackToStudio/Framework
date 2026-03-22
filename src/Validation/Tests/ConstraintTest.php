<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Tests;

use BackTo\Framework\Validation\Constraint\Callback;
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
use BackTo\Framework\Validation\Constraint\Unique;
use BackTo\Framework\Validation\Constraint\Url;
use PHPUnit\Framework\TestCase;

class ConstraintTest extends TestCase
{
    // --- NotBlank ---

    public function testNotBlankRejectsNull(): void
    {
        $this->assertNotNull((new NotBlank())->validate(null));
    }

    public function testNotBlankRejectsEmptyString(): void
    {
        $this->assertNotNull((new NotBlank())->validate(''));
    }

    public function testNotBlankRejectsWhitespace(): void
    {
        $this->assertNotNull((new NotBlank())->validate('   '));
    }

    public function testNotBlankRejectsEmptyArray(): void
    {
        $this->assertNotNull((new NotBlank())->validate([]));
    }

    public function testNotBlankAcceptsValue(): void
    {
        $this->assertNull((new NotBlank())->validate('hello'));
    }

    public function testNotBlankAcceptsZero(): void
    {
        $this->assertNull((new NotBlank())->validate(0));
    }

    // --- NotNull ---

    public function testNotNullRejectsNull(): void
    {
        $this->assertNotNull((new NotNull())->validate(null));
    }

    public function testNotNullAcceptsEmptyString(): void
    {
        $this->assertNull((new NotNull())->validate(''));
    }

    // --- Email ---

    public function testEmailAcceptsValid(): void
    {
        $this->assertNull((new Email())->validate('user@example.com'));
    }

    public function testEmailRejectsInvalid(): void
    {
        $this->assertNotNull((new Email())->validate('not-email'));
    }

    public function testEmailSkipsNull(): void
    {
        $this->assertNull((new Email())->validate(null));
    }

    public function testEmailSkipsEmpty(): void
    {
        $this->assertNull((new Email())->validate(''));
    }

    public function testEmailRejectsNonString(): void
    {
        $this->assertNotNull((new Email())->validate(42));
    }

    // --- Length ---

    public function testLengthMinValid(): void
    {
        $this->assertNull((new Length(min: 3))->validate('abc'));
    }

    public function testLengthMinInvalid(): void
    {
        $this->assertNotNull((new Length(min: 3))->validate('ab'));
    }

    public function testLengthMaxValid(): void
    {
        $this->assertNull((new Length(max: 5))->validate('abcde'));
    }

    public function testLengthMaxInvalid(): void
    {
        $this->assertNotNull((new Length(max: 5))->validate('abcdef'));
    }

    public function testLengthMinMaxValid(): void
    {
        $this->assertNull((new Length(min: 2, max: 5))->validate('abc'));
    }

    public function testLengthSkipsNull(): void
    {
        $this->assertNull((new Length(min: 1))->validate(null));
    }

    public function testLengthRejectsNonString(): void
    {
        $this->assertNotNull((new Length(min: 1))->validate(123));
    }

    // --- Regex ---

    public function testRegexValid(): void
    {
        $this->assertNull((new Regex('/^\d+$/'))->validate('123'));
    }

    public function testRegexInvalid(): void
    {
        $this->assertNotNull((new Regex('/^\d+$/'))->validate('abc'));
    }

    public function testRegexSkipsNull(): void
    {
        $this->assertNull((new Regex('/^\d+$/'))->validate(null));
    }

    // --- Choice ---

    public function testChoiceValid(): void
    {
        $this->assertNull((new Choice(['a', 'b', 'c']))->validate('b'));
    }

    public function testChoiceInvalid(): void
    {
        $this->assertNotNull((new Choice(['a', 'b']))->validate('z'));
    }

    public function testChoiceSkipsNull(): void
    {
        $this->assertNull((new Choice(['a']))->validate(null));
    }

    public function testChoiceUsesStrictComparison(): void
    {
        $this->assertNotNull((new Choice([1, 2, 3]))->validate('1'));
    }

    // --- Type ---

    public function testTypeStringValid(): void
    {
        $this->assertNull((new Type('string'))->validate('hello'));
    }

    public function testTypeStringInvalid(): void
    {
        $this->assertNotNull((new Type('string'))->validate(42));
    }

    public function testTypeIntValid(): void
    {
        $this->assertNull((new Type('int'))->validate(42));
    }

    public function testTypeBoolValid(): void
    {
        $this->assertNull((new Type('bool'))->validate(true));
    }

    public function testTypeArrayValid(): void
    {
        $this->assertNull((new Type('array'))->validate([1, 2]));
    }

    public function testTypeNumericValid(): void
    {
        $this->assertNull((new Type('numeric'))->validate('3.14'));
    }

    public function testTypeSkipsNull(): void
    {
        $this->assertNull((new Type('string'))->validate(null));
    }

    // --- Range ---

    public function testRangeMinValid(): void
    {
        $this->assertNull((new Range(min: 5))->validate(10));
    }

    public function testRangeMinInvalid(): void
    {
        $this->assertNotNull((new Range(min: 5))->validate(3));
    }

    public function testRangeMaxValid(): void
    {
        $this->assertNull((new Range(max: 10))->validate(5));
    }

    public function testRangeMaxInvalid(): void
    {
        $this->assertNotNull((new Range(max: 10))->validate(15));
    }

    public function testRangeMinMaxValid(): void
    {
        $this->assertNull((new Range(min: 1, max: 10))->validate(5));
    }

    public function testRangeRejectsNonNumeric(): void
    {
        $this->assertNotNull((new Range(min: 0))->validate('abc'));
    }

    public function testRangeSkipsNull(): void
    {
        $this->assertNull((new Range(min: 0))->validate(null));
    }

    // --- Url ---

    public function testUrlValid(): void
    {
        $this->assertNull((new Url())->validate('https://example.com'));
    }

    public function testUrlInvalid(): void
    {
        $this->assertNotNull((new Url())->validate('not a url'));
    }

    public function testUrlSkipsNull(): void
    {
        $this->assertNull((new Url())->validate(null));
    }

    // --- Count ---

    public function testCountMinValid(): void
    {
        $this->assertNull((new Count(min: 2))->validate([1, 2, 3]));
    }

    public function testCountMinInvalid(): void
    {
        $this->assertNotNull((new Count(min: 3))->validate([1]));
    }

    public function testCountMaxValid(): void
    {
        $this->assertNull((new Count(max: 3))->validate([1, 2]));
    }

    public function testCountMaxInvalid(): void
    {
        $this->assertNotNull((new Count(max: 2))->validate([1, 2, 3]));
    }

    public function testCountRejectsNonArray(): void
    {
        $this->assertNotNull((new Count(min: 1))->validate('string'));
    }

    // --- Unique ---

    public function testUniqueValid(): void
    {
        $this->assertNull((new Unique())->validate([1, 2, 3]));
    }

    public function testUniqueInvalid(): void
    {
        $this->assertNotNull((new Unique())->validate([1, 2, 2]));
    }

    public function testUniqueRejectsNonArray(): void
    {
        $this->assertNotNull((new Unique())->validate('string'));
    }

    // --- Each ---

    public function testEachValid(): void
    {
        $constraint = new Each(new Email());
        $this->assertNull($constraint->validate(['a@b.com', 'c@d.com']));
    }

    public function testEachInvalid(): void
    {
        $constraint = new Each(new Email());
        $this->assertNotNull($constraint->validate(['a@b.com', 'bad']));
    }

    public function testEachSkipsNull(): void
    {
        $this->assertNull((new Each(new NotBlank()))->validate(null));
    }

    // --- Callback ---

    public function testCallbackValid(): void
    {
        $constraint = new Callback(static fn ($v) => $v === 'ok' ? null : 'fail');
        $this->assertNull($constraint->validate('ok'));
    }

    public function testCallbackInvalid(): void
    {
        $constraint = new Callback(static fn ($v) => $v === 'ok' ? null : 'fail');
        $this->assertNotNull($constraint->validate('bad'));
    }

    // --- Custom message ---

    public function testCustomMessage(): void
    {
        $constraint = new NotBlank('Le champ est obligatoire.');
        $this->assertSame('Le champ est obligatoire.', $constraint->validate(''));
    }
}
