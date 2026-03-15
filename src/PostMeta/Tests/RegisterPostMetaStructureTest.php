<?php

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaRegistrarInterface;
use BackTo\Framework\PostMeta\Entity\PostMetaStructure;
use BackTo\Framework\PostMeta\PostMetaStructureRegistry;
use BackTo\Framework\PostMeta\RegisterPostMetaStructure;
use PHPUnit\Framework\TestCase;

class RegisterPostMetaStructureTest extends TestCase
{
    public function testHooksRegistersInitAction(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $registrar = $this->createMock(PostMetaRegistrarInterface::class);
        $registry = new PostMetaStructureRegistry();

        $register = new RegisterPostMetaStructure($registry, $registrar, $hookDispatcher);
        $register->hooks();
    }

    public function testRegisterPostMetaCallsRegistrar(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $registrar = $this->createMock(PostMetaRegistrarInterface::class);
        $registrar->expects($this->once())
            ->method('register')
            ->with('post', 'my_meta', $this->anything());

        $registry = new PostMetaStructureRegistry();

        $meta = new PostMetaStructure();
        $meta->setObjectType('post');
        $meta->setMetaKey('my_meta');
        $meta->setType('string');
        $meta->setLabel('My Meta');
        $meta->setDescription('A test meta field');
        $registry->add($meta);

        $register = new RegisterPostMetaStructure($registry, $registrar, $hookDispatcher);
        $register->registerPostMeta();
    }
}
