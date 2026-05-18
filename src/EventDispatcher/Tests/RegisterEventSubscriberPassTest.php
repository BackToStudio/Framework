<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Tests;

use BackTo\Framework\EventDispatcher\DependencyInjection\Compiler\RegisterEventSubscriberPass;
use BackTo\Framework\EventDispatcher\EventDispatcher;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Definition;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\EventDispatcher\DependencyInjection\Compiler\RegisterEventSubscriberPass
 */
class RegisterEventSubscriberPassTest extends TestCase
{
    public function testProcessRegistersTaggedSubscribersWithDispatcher(): void
    {
        $container = new ContainerBuilder();

        $dispatcherDef = new Definition(EventDispatcher::class);
        $container->setDefinition(EventDispatcher::class, $dispatcherDef);

        $subscriberDef = new Definition(TestSubscriber::class);
        $subscriberDef->addTag('backto.event_subscriber');
        $container->setDefinition(TestSubscriber::class, $subscriberDef);

        $pass = new RegisterEventSubscriberPass();
        $pass->process($container);

        $methodCalls = $dispatcherDef->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertSame('addSubscriber', $methodCalls[0][0]);
    }

    public function testProcessDoesNothingWithoutDispatcherDefinition(): void
    {
        $container = new ContainerBuilder();

        $subscriberDef = new Definition(TestSubscriber::class);
        $subscriberDef->addTag('backto.event_subscriber');
        $container->setDefinition(TestSubscriber::class, $subscriberDef);

        $pass = new RegisterEventSubscriberPass();
        $pass->process($container);

        // No exception — silently skips.
        $this->assertTrue(true);
    }

    public function testProcessWithNoTaggedServicesAddsNoMethodCalls(): void
    {
        $container = new ContainerBuilder();

        $dispatcherDef = new Definition(EventDispatcher::class);
        $container->setDefinition(EventDispatcher::class, $dispatcherDef);

        $pass = new RegisterEventSubscriberPass();
        $pass->process($container);

        $this->assertCount(0, $dispatcherDef->getMethodCalls());
    }

    public function testProcessRegistersMultipleSubscribers(): void
    {
        $container = new ContainerBuilder();

        $dispatcherDef = new Definition(EventDispatcher::class);
        $container->setDefinition(EventDispatcher::class, $dispatcherDef);

        $sub1 = new Definition(TestSubscriber::class);
        $sub1->addTag('backto.event_subscriber');
        $container->setDefinition('subscriber_one', $sub1);

        $sub2 = new Definition(MultiListenerSubscriber::class);
        $sub2->addTag('backto.event_subscriber');
        $container->setDefinition('subscriber_two', $sub2);

        $pass = new RegisterEventSubscriberPass();
        $pass->process($container);

        $methodCalls = $dispatcherDef->getMethodCalls();
        $this->assertCount(2, $methodCalls);

        foreach ($methodCalls as $call) {
            $this->assertSame('addSubscriber', $call[0]);
        }
    }
}
