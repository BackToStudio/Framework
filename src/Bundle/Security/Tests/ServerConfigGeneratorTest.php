<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\Contracts\FileWriterInterface;
use BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator;
use BackTo\Framework\Bundle\Security\SecurityConfiguration;
use PHPUnit\Framework\TestCase;

class ServerConfigGeneratorTest extends TestCase
{
    private ServerConfigGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ServerConfigGenerator();

        // Apply defaults as the compiler pass would
        $defaults = SecurityConfiguration::getDefaults();
        $this->generator
            ->setBlockedUserAgents($defaults['security.bot_protection.blocked_user_agents'])
            ->setBlockedIps($defaults['security.bot_protection.blocked_ips'])
            ->setSensitiveEndpoints($defaults['security.bot_protection.sensitive_endpoints'])
            ->setGlobalRateLimit(
                $defaults['security.bot_protection.global_rate_limit'],
                $defaults['security.bot_protection.global_burst'],
            )
            ->setSensitiveRateLimit(
                $defaults['security.bot_protection.sensitive_rate_limit'],
                $defaults['security.bot_protection.sensitive_burst'],
            )
            ->setMaxConnectionsPerIp($defaults['security.bot_protection.max_connections_per_ip'])
            ->setBlockEmptyUserAgent($defaults['security.bot_protection.block_empty_user_agent']);
    }

    public function testGenerateNginxContainsRateLimitZones(): void
    {
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('limit_req_zone', $output);
        $this->assertStringContainsString('backto_global', $output);
        $this->assertStringContainsString('backto_sensitive', $output);
        $this->assertStringContainsString('limit_conn_zone', $output);
    }

    public function testGenerateNginxContainsConfiguredUserAgentBlocking(): void
    {
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('SemrushBot', $output);
        $this->assertStringContainsString('AhrefsBot', $output);
        $this->assertStringContainsString('GPTBot', $output);
        $this->assertStringContainsString('return 403', $output);
    }

    public function testGenerateNginxBlocksEmptyUserAgentByDefault(): void
    {
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('if ($http_user_agent = "")', $output);
    }

    public function testGenerateNginxCanDisableEmptyUserAgentBlocking(): void
    {
        $this->generator->setBlockEmptyUserAgent(false);
        $output = $this->generator->generateNginx();

        $this->assertStringNotContainsString('if ($http_user_agent = "")', $output);
    }

    public function testGenerateNginxIncludesBlockedIps(): void
    {
        $this->generator->setBlockedIps(['192.0.2.0/24', '10.0.0.1']);
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('deny 192.0.2.0/24;', $output);
        $this->assertStringContainsString('deny 10.0.0.1;', $output);
    }

    public function testGenerateNginxOmitsIpBlockWhenEmpty(): void
    {
        $output = $this->generator->generateNginx();

        $this->assertStringNotContainsString('deny ', $output);
    }

    public function testGenerateNginxCustomRateLimits(): void
    {
        $this->generator->setGlobalRateLimit(20, 40);
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('rate=20r/s', $output);
        $this->assertStringContainsString('burst=40', $output);
    }

    public function testGenerateNginxSensitiveEndpoints(): void
    {
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('wp\-login\.php', $output);
        $this->assertStringContainsString('xmlrpc\.php', $output);
        $this->assertStringContainsString('backto_sensitive', $output);
    }

    public function testGenerateNginxCustomMaxConnections(): void
    {
        $this->generator->setMaxConnectionsPerIp(10);
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('limit_conn backto_conn 10;', $output);
    }

    public function testGenerateApacheContainsUserAgentBlocking(): void
    {
        $output = $this->generator->generateApache();

        $this->assertStringContainsString('RewriteEngine On', $output);
        $this->assertStringContainsString('SemrushBot', $output);
        $this->assertStringContainsString('HTTP_USER_AGENT', $output);
        $this->assertStringContainsString('[F,L]', $output);
    }

    public function testGenerateApacheBlocksEmptyUserAgentByDefault(): void
    {
        $output = $this->generator->generateApache();

        $this->assertStringContainsString('RewriteCond %{HTTP_USER_AGENT} ^$', $output);
    }

    public function testGenerateApacheContainsModEvasive(): void
    {
        $output = $this->generator->generateApache();

        $this->assertStringContainsString('mod_evasive24', $output);
        $this->assertStringContainsString('DOSPageCount', $output);
        $this->assertStringContainsString('DOSSiteCount', $output);
        $this->assertStringContainsString('DOSBlockingPeriod', $output);
    }

    public function testGenerateApacheIncludesBlockedIps(): void
    {
        $this->generator->setBlockedIps(['192.0.2.0/24']);
        $output = $this->generator->generateApache();

        $this->assertStringContainsString('Require not ip 192.0.2.0/24', $output);
    }

    public function testGenerateApacheOmitsIpBlockWhenEmpty(): void
    {
        $output = $this->generator->generateApache();

        $this->assertStringNotContainsString('Require not ip', $output);
    }

    public function testSetBlockedUserAgentsReplacesConfig(): void
    {
        $this->generator->setBlockedUserAgents(['CustomBot']);
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('CustomBot', $output);
        $this->assertStringNotContainsString('SemrushBot', $output);
    }

    public function testAddBlockedUserAgentsMergesWithExisting(): void
    {
        $this->generator->addBlockedUserAgents(['CustomBot']);
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('CustomBot', $output);
        $this->assertStringContainsString('SemrushBot', $output);
    }

    public function testSetSensitiveEndpoints(): void
    {
        $this->generator->setSensitiveEndpoints(['custom-admin.php']);
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('custom\-admin\.php', $output);
        $this->assertStringNotContainsString('wp\-login', $output);
    }

    public function testWriteNginxUsesFileWriter(): void
    {
        $writer = $this->createMock(FileWriterInterface::class);
        $writer->expects($this->once())
            ->method('write')
            ->with('/tmp/test.conf', $this->stringContains('limit_req_zone'))
            ->willReturn(true);

        $generator = new ServerConfigGenerator($writer);
        $result = $generator->writeNginx('/tmp/test.conf');

        $this->assertTrue($result);
    }

    public function testWriteApacheUsesFileWriter(): void
    {
        $writer = $this->createMock(FileWriterInterface::class);
        $writer->expects($this->once())
            ->method('write')
            ->with('/tmp/.htaccess', $this->stringContains('mod_evasive'))
            ->willReturn(true);

        $generator = new ServerConfigGenerator($writer);
        $result = $generator->writeApache('/tmp/.htaccess');

        $this->assertTrue($result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->generator
            ->setBlockedUserAgents(['Bot1'])
            ->addBlockedUserAgents(['Bot2'])
            ->setSensitiveEndpoints(['admin.php'])
            ->setGlobalRateLimit(5, 10)
            ->setSensitiveRateLimit(1, 2)
            ->setMaxConnectionsPerIp(5)
            ->setBlockEmptyUserAgent(true)
            ->setBlockedIps(['10.0.0.1']);

        $this->assertSame($this->generator, $result);
    }

    public function testGenerateNginxContainsHeader(): void
    {
        $output = $this->generator->generateNginx();

        $this->assertStringContainsString('BackTo Framework Security Bundle', $output);
    }

    public function testGenerateApacheContainsHeader(): void
    {
        $output = $this->generator->generateApache();

        $this->assertStringContainsString('BackTo Framework Security Bundle', $output);
    }

    public function testGetConfigurationReturnsCurrentState(): void
    {
        $this->generator
            ->setBlockedUserAgents(['Bot1'])
            ->setBlockedIps(['10.0.0.1'])
            ->setGlobalRateLimit(5, 10)
            ->setMaxConnectionsPerIp(15);

        $config = $this->generator->getConfiguration();

        $this->assertSame(['Bot1'], $config['blocked_user_agents']);
        $this->assertSame(['10.0.0.1'], $config['blocked_ips']);
        $this->assertSame(5, $config['global_rate_limit']);
        $this->assertSame(10, $config['global_burst']);
        $this->assertSame(15, $config['max_connections_per_ip']);
    }

    public function testEmptyGeneratorProducesMinimalOutput(): void
    {
        $bare = new ServerConfigGenerator();
        $output = $bare->generateNginx();

        // Should still have rate limiting zones even without user agents
        $this->assertStringContainsString('limit_req_zone', $output);
        // No user agent block
        $this->assertStringNotContainsString('http_user_agent ~*', $output);
    }
}
