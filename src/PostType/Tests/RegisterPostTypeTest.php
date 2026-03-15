<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;
use BackTo\Framework\PostType\PostTypeFactory;
use BackTo\Framework\PostType\PostTypeRegistry;
use BackTo\Framework\PostType\RegisterPostType;
use PHPUnit\Framework\TestCase;

class RegisterPostTypeTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private PostTypeRegistrarInterface $registrar;
    private PostTypeRegistry $registry;
    private PostTypeFactory $factory;
    private RegisterPostType $registerPostType;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->registrar = $this->createMock(PostTypeRegistrarInterface::class);
        $this->registry = new PostTypeRegistry();
        $this->factory = new PostTypeFactory();

        $this->registerPostType = new RegisterPostType(
            $this->registry,
            $this->factory,
            $this->registrar,
            $this->hookDispatcher
        );
    }

    public function testHooksRegistersInitAction(): void
    {
        $this->hookDispatcher->expects($this->atLeastOnce())
            ->method('addAction')
            ->with(
                $this->callback(fn(string $hook) => $hook === 'init' || str_starts_with($hook, 'registered_') || str_starts_with($hook, 'unregistered_')),
                $this->anything()
            );

        $this->registerPostType->hooks();
    }

    public function testRegisterCustomPostTypesCallsRegistrar(): void
    {
        $this->registrar->method('exists')->willReturn(false);
        $this->registrar->expects($this->once())
            ->method('register')
            ->with('book', $this->anything());

        $this->registerPostType->add('book', ['label' => 'Books']);
        $this->registerPostType->registerCustomPostTypes();
    }

    public function testRegisterCustomPostTypesSkipsExistingPostType(): void
    {
        $this->registrar->method('exists')->willReturn(true);
        $this->registrar->expects($this->never())->method('register');

        $this->registerPostType->add('book');
        $this->registerPostType->registerCustomPostTypes();
    }

    public function testActivateCallsFlushRewriteRules(): void
    {
        $this->registrar->expects($this->once())->method('flushRewriteRules');
        $this->registrar->method('exists')->willReturn(false);

        $this->registerPostType->activate();
    }

    public function testAddReturnsFluentInterface(): void
    {
        $result = $this->registerPostType->add('book');
        $this->assertSame($this->registerPostType, $result);
    }
}
