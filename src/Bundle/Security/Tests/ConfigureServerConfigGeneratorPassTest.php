<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\DependencyInjection\Compiler\ConfigureServerConfigGeneratorPass;
use BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class ConfigureServerConfigGeneratorPassTest extends TestCase
{
    private ContainerBuilder $container;
    private ConfigureServerConfigGeneratorPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new ConfigureServerConfigGeneratorPass();
    }

    public function testSkipsWhenServiceNotRegistered(): void
    {
        // Should not throw
        $this->pass->process($this->container);
        $this->assertFalse($this->container->hasDefinition(ServerConfigGenerator::class));
    }

    public function testInjectsBlockedUserAgents(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.blocked_user_agents', ['TestBot']);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallExists($calls, 'setBlockedUserAgents', [['TestBot']]);
    }

    public function testInjectsBlockedIps(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.blocked_ips', ['10.0.0.1']);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallExists($calls, 'setBlockedIps', [['10.0.0.1']]);
    }

    public function testInjectsGlobalRateLimit(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.global_rate_limit', 20);
        $this->container->setParameter('security.bot_protection.global_burst', 40);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallExists($calls, 'setGlobalRateLimit', [20, 40]);
    }

    public function testInjectsSensitiveRateLimit(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.sensitive_rate_limit', 1);
        $this->container->setParameter('security.bot_protection.sensitive_burst', 2);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallExists($calls, 'setSensitiveRateLimit', [1, 2]);
    }

    public function testInjectsMaxConnectionsPerIp(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.max_connections_per_ip', 10);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallExists($calls, 'setMaxConnectionsPerIp', [10]);
    }

    public function testInjectsBlockEmptyUserAgent(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.block_empty_user_agent', false);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallExists($calls, 'setBlockEmptyUserAgent', [false]);
    }

    public function testInjectsSensitiveEndpoints(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.sensitive_endpoints', ['custom.php']);

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallExists($calls, 'setSensitiveEndpoints', [['custom.php']]);
    }

    public function testSkipsGlobalRateLimitWhenBurstMissing(): void
    {
        $this->registerService();
        $this->container->setParameter('security.bot_protection.global_rate_limit', 20);
        // No burst parameter

        $this->pass->process($this->container);

        $calls = $this->container->getDefinition(ServerConfigGenerator::class)->getMethodCalls();
        $this->assertMethodCallNotExists($calls, 'setGlobalRateLimit');
    }

    private function registerService(): void
    {
        $this->container->register(ServerConfigGenerator::class, ServerConfigGenerator::class);
    }

    /**
     * @param array<int, array{0: string, 1: mixed[]}> $calls
     * @param mixed[] $expectedArgs
     */
    private function assertMethodCallExists(array $calls, string $method, array $expectedArgs): void
    {
        foreach ($calls as [$name, $args]) {
            if ($name === $method) {
                $this->assertSame($expectedArgs, $args);

                return;
            }
        }

        $this->fail(sprintf('Method call "%s" not found in definition.', $method));
    }

    /**
     * @param array<int, array{0: string, 1: mixed[]}> $calls
     */
    private function assertMethodCallNotExists(array $calls, string $method): void
    {
        foreach ($calls as [$name, $args]) {
            if ($name === $method) {
                $this->fail(sprintf('Method call "%s" should not exist in definition.', $method));
            }
        }

        $this->assertTrue(true);
    }
}
