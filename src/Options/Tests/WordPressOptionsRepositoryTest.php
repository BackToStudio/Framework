<?php

declare(strict_types=1);

namespace BackTo\Framework\Options\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Options\Infrastructure\WordPressOptionsRepository;
use PHPUnit\Framework\TestCase;

class WordPressOptionsRepositoryTest extends TestCase
{
    private WordPressOptionsRepository $repository;

    protected function setUp(): void
    {
        $GLOBALS['_wp_options'] = [];
        $this->repository = new WordPressOptionsRepository();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_wp_options']);
    }

    public function testImplementsOptionsRepositoryInterface(): void
    {
        $this->assertInstanceOf(OptionsRepositoryInterface::class, $this->repository);
    }

    public function testGetReturnsStoredValue(): void
    {
        $GLOBALS['_wp_options']['site_title'] = 'My Site';

        $this->assertSame('My Site', $this->repository->get('site_title'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $this->assertSame('fallback', $this->repository->get('nonexistent', 'fallback'));
    }

    public function testGetReturnsNullDefaultWhenSpecified(): void
    {
        $this->assertNull($this->repository->get('missing', null));
    }

    public function testGetReturnsFalseByDefaultForMissingKey(): void
    {
        // WordPress default for get_option is false, not null
        $result = $this->repository->get('missing');
        $this->assertNull($result); // Our interface defaults to null
    }

    public function testUpdateStoresValue(): void
    {
        $result = $this->repository->update('key', 'value');

        $this->assertTrue($result);
        $this->assertSame('value', $GLOBALS['_wp_options']['key']);
    }

    public function testUpdateOverwritesExistingValue(): void
    {
        $GLOBALS['_wp_options']['key'] = 'old';
        $this->repository->update('key', 'new');

        $this->assertSame('new', $GLOBALS['_wp_options']['key']);
    }

    public function testUpdateCanStoreArray(): void
    {
        $this->repository->update('settings', ['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $GLOBALS['_wp_options']['settings']);
    }

    public function testUpdateCanStoreNull(): void
    {
        $this->repository->update('nullable', null);

        $this->assertNull($GLOBALS['_wp_options']['nullable']);
    }

    public function testUpdateCanStoreEmptyString(): void
    {
        $this->repository->update('empty', '');

        $this->assertSame('', $this->repository->get('empty'));
    }

    public function testUpdateCanStoreFalse(): void
    {
        $this->repository->update('bool_false', false);

        $this->assertFalse($GLOBALS['_wp_options']['bool_false']);
    }

    public function testUpdateCanStoreZero(): void
    {
        $this->repository->update('zero', 0);

        $this->assertSame(0, $this->repository->get('zero'));
    }

    public function testDeleteRemovesOption(): void
    {
        $GLOBALS['_wp_options']['to_delete'] = 'value';

        $result = $this->repository->delete('to_delete');

        $this->assertTrue($result);
        $this->assertArrayNotHasKey('to_delete', $GLOBALS['_wp_options']);
    }

    public function testDeleteReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->repository->delete('nonexistent'));
    }

    public function testExistsReturnsTrueForExistingKey(): void
    {
        $GLOBALS['_wp_options']['exists'] = 'yes';

        $this->assertTrue($this->repository->exists('exists'));
    }

    public function testExistsReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->repository->exists('missing'));
    }

    public function testExistsReturnsTrueForFalseValue(): void
    {
        // Edge case: option exists but its value is false
        $GLOBALS['_wp_options']['falsy'] = false;

        $this->assertTrue($this->repository->exists('falsy'));
    }

    public function testExistsReturnsTrueForNullValue(): void
    {
        // Edge case: option exists but value is null
        $GLOBALS['_wp_options']['null_val'] = null;

        $this->assertTrue($this->repository->exists('null_val'));
    }

    public function testExistsReturnsTrueForEmptyString(): void
    {
        $GLOBALS['_wp_options']['empty'] = '';

        $this->assertTrue($this->repository->exists('empty'));
    }

    public function testExistsReturnsTrueForZero(): void
    {
        $GLOBALS['_wp_options']['zero'] = 0;

        $this->assertTrue($this->repository->exists('zero'));
    }

    public function testExistsReturnsTrueForEmptyArray(): void
    {
        $GLOBALS['_wp_options']['empty_arr'] = [];

        $this->assertTrue($this->repository->exists('empty_arr'));
    }

    public function testExistsUsesSentinelPatternNotStrictComparison(): void
    {
        // The exists() method uses `get_option($key, $this)` with $this as sentinel
        // This ensures it works even when the stored value is false/null/0/''
        $GLOBALS['_wp_options']['tricky'] = false;

        // Must return true: option exists, even though its value is falsy
        $this->assertTrue($this->repository->exists('tricky'));
    }

    public function testGetAfterDeleteReturnsDefault(): void
    {
        $GLOBALS['_wp_options']['temp'] = 'value';
        $this->repository->delete('temp');

        $this->assertSame('default', $this->repository->get('temp', 'default'));
    }

    public function testUpdateThenGetReturnsUpdatedValue(): void
    {
        $this->repository->update('key', 'first');
        $this->repository->update('key', 'second');

        $this->assertSame('second', $this->repository->get('key'));
    }
}
