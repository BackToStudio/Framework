<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Hooks\Infrastructure\WordPressHookDispatcher;
use PHPUnit\Framework\TestCase;

class WordPressHookDispatcherTest extends TestCase
{
    private WordPressHookDispatcher $dispatcher;

    protected function setUp(): void
    {
        $GLOBALS['_wp_hooks'] = [
            'actions' => [],
            'filters' => [],
            'removed_actions' => [],
            'activation' => [],
            'deactivation' => [],
        ];
        $GLOBALS['_wp_is_admin'] = false;
        $this->dispatcher = new WordPressHookDispatcher();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_wp_hooks'], $GLOBALS['_wp_is_admin']);
    }

    public function testImplementsHookDispatcherInterface(): void
    {
        $this->assertInstanceOf(HookDispatcherInterface::class, $this->dispatcher);
    }

    public function testAddActionDelegatesToWordPress(): void
    {
        $callback = fn() => null;
        $this->dispatcher->addAction('init', $callback, 20, 2);

        $this->assertCount(1, $GLOBALS['_wp_hooks']['actions']);
        $action = $GLOBALS['_wp_hooks']['actions'][0];
        $this->assertSame('init', $action['tag']);
        $this->assertSame($callback, $action['callback']);
        $this->assertSame(20, $action['priority']);
        $this->assertSame(2, $action['acceptedArgs']);
    }

    public function testAddActionUsesDefaultPriority(): void
    {
        $this->dispatcher->addAction('init', fn() => null);

        $action = $GLOBALS['_wp_hooks']['actions'][0];
        $this->assertSame(10, $action['priority']);
        $this->assertSame(1, $action['acceptedArgs']);
    }

    public function testAddFilterDelegatesToWordPress(): void
    {
        $callback = fn(string $content) => $content;
        $this->dispatcher->addFilter('the_content', $callback, 5, 1);

        $this->assertCount(1, $GLOBALS['_wp_hooks']['filters']);
        $filter = $GLOBALS['_wp_hooks']['filters'][0];
        $this->assertSame('the_content', $filter['tag']);
        $this->assertSame($callback, $filter['callback']);
        $this->assertSame(5, $filter['priority']);
    }

    public function testRemoveActionDelegatesToWordPress(): void
    {
        $this->dispatcher->removeAction('wp_head', 'wp_generator', 15);

        $this->assertCount(1, $GLOBALS['_wp_hooks']['removed_actions']);
        $removed = $GLOBALS['_wp_hooks']['removed_actions'][0];
        $this->assertSame('wp_head', $removed['tag']);
        $this->assertSame('wp_generator', $removed['callback']);
        $this->assertSame(15, $removed['priority']);
    }

    public function testRemoveActionUsesDefaultPriority(): void
    {
        $this->dispatcher->removeAction('wp_head', 'wp_generator');

        $removed = $GLOBALS['_wp_hooks']['removed_actions'][0];
        $this->assertSame(10, $removed['priority']);
    }

    public function testIsAdminReturnsFalseByDefault(): void
    {
        $GLOBALS['_wp_is_admin'] = false;
        $this->assertFalse($this->dispatcher->isAdmin());
    }

    public function testIsAdminReturnsTrueWhenInAdminContext(): void
    {
        $GLOBALS['_wp_is_admin'] = true;
        $this->assertTrue($this->dispatcher->isAdmin());
    }

    public function testRegisterActivationHook(): void
    {
        $callback = fn() => null;
        $this->dispatcher->registerActivationHook('my-plugin.php', $callback);

        $this->assertCount(1, $GLOBALS['_wp_hooks']['activation']);
        $this->assertSame('my-plugin.php', $GLOBALS['_wp_hooks']['activation'][0]['file']);
        $this->assertSame($callback, $GLOBALS['_wp_hooks']['activation'][0]['callback']);
    }

    public function testRegisterDeactivationHook(): void
    {
        $callback = fn() => null;
        $this->dispatcher->registerDeactivationHook('my-plugin.php', $callback);

        $this->assertCount(1, $GLOBALS['_wp_hooks']['deactivation']);
        $this->assertSame('my-plugin.php', $GLOBALS['_wp_hooks']['deactivation'][0]['file']);
    }

    public function testAddActionWithStringCallback(): void
    {
        $this->dispatcher->addAction('init', 'my_init_function');

        $action = $GLOBALS['_wp_hooks']['actions'][0];
        $this->assertSame('my_init_function', $action['callback']);
    }

    public function testAddFilterWithArrayCallback(): void
    {
        $callback = [$this, 'testAddFilterWithArrayCallback'];
        $this->dispatcher->addFilter('the_title', $callback);

        $filter = $GLOBALS['_wp_hooks']['filters'][0];
        $this->assertSame($callback, $filter['callback']);
    }

    public function testMultipleActionsCanBeRegistered(): void
    {
        $this->dispatcher->addAction('init', fn() => null);
        $this->dispatcher->addAction('wp_loaded', fn() => null);
        $this->dispatcher->addAction('init', fn() => null, 20);

        $this->assertCount(3, $GLOBALS['_wp_hooks']['actions']);
    }

    public function testAddActionWithHighPriority(): void
    {
        $this->dispatcher->addAction('init', fn() => null, PHP_INT_MAX);

        $this->assertSame(PHP_INT_MAX, $GLOBALS['_wp_hooks']['actions'][0]['priority']);
    }

    public function testAddActionWithNegativePriority(): void
    {
        $this->dispatcher->addAction('init', fn() => null, -1);

        $this->assertSame(-1, $GLOBALS['_wp_hooks']['actions'][0]['priority']);
    }
}
