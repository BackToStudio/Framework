<?php

namespace BackTo\Framework\Taxonomy\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyRegistrarInterface;
use BackTo\Framework\Taxonomy\RegisterTaxonomy;
use BackTo\Framework\Taxonomy\TaxonomyFactory;
use BackTo\Framework\Taxonomy\TaxonomyRegistry;
use PHPUnit\Framework\TestCase;

class RegisterTaxonomyTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private TaxonomyRegistrarInterface $registrar;
    private TaxonomyRegistry $registry;
    private TaxonomyFactory $factory;
    private RegisterTaxonomy $registerTaxonomy;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->registrar = $this->createMock(TaxonomyRegistrarInterface::class);
        $this->registry = new TaxonomyRegistry();
        $this->factory = new TaxonomyFactory();

        $this->registerTaxonomy = new RegisterTaxonomy(
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

        $this->registerTaxonomy->hooks();
    }

    public function testRegisterTaxonomyCallsRegistrar(): void
    {
        $this->registrar->method('exists')->willReturn(false);
        $this->registrar->expects($this->once())
            ->method('register')
            ->with('genre', $this->anything(), $this->anything());

        $this->registerTaxonomy->add('genre', ['post']);
        $this->registerTaxonomy->registerTaxonomy();
    }

    public function testRegisterTaxonomySkipsExistingTaxonomy(): void
    {
        $this->registrar->method('exists')->willReturn(true);
        $this->registrar->expects($this->never())->method('register');

        $this->registerTaxonomy->add('genre', ['post']);
        $this->registerTaxonomy->registerTaxonomy();
    }

    public function testAddReturnsFluentInterface(): void
    {
        $result = $this->registerTaxonomy->add('genre', ['post']);
        $this->assertSame($this->registerTaxonomy, $result);
    }
}
