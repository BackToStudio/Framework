<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Security\Contracts\LoginLocationRepositoryInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\LoginAnomalyDetector;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control server variables and user resolution.
 */
class TestableLoginAnomalyDetector extends LoginAnomalyDetector
{
    private string $clientIp = '1.2.3.4';
    private string $country = 'US';
    private string $userAgent = 'TestBrowser/1.0';
    private ?int $resolvedUserId = null;

    public function setClientIp(string $ip): void
    {
        $this->clientIp = $ip;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function setUserAgent(string $ua): void
    {
        $this->userAgent = $ua;
    }

    public function setResolvedUserId(?int $id): void
    {
        $this->resolvedUserId = $id;
    }

    protected function getClientIp(): string
    {
        return $this->clientIp;
    }

    protected function getUserAgent(): string
    {
        return $this->userAgent;
    }

    protected function getUserId(mixed $user): ?int
    {
        return $this->resolvedUserId;
    }

    protected function getCountryFromIp(string $ip): string
    {
        return $this->country;
    }
}

class LoginAnomalyDetectorTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoginLocationRepositoryInterface $repository;
    private LoggerInterface $logger;
    private RequestContextInterface $requestContext;
    private ClientIpResolverInterface $ipResolver;
    private TestableLoginAnomalyDetector $detector;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->repository = $this->createMock(LoginLocationRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->requestContext->method('getRemoteAddr')->willReturn('127.0.0.1');
        $this->requestContext->method('server')->willReturn('');
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('127.0.0.1');

        $this->detector = new TestableLoginAnomalyDetector(
            $this->dispatcher,
            $this->repository,
            $this->logger,
            $this->requestContext,
            $this->ipResolver,
        );
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->detector);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->detector);
    }

    public function testGetName(): void
    {
        $this->assertSame('login_anomaly_detector', $this->detector->getName());
    }

    public function testHooksRegistersWpLogin(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp_login', $this->anything(), 20, 2);

        $this->detector->hooks();
    }

    public function testDetectAnomaliesNoAnomalyForKnownCountry(): void
    {
        $this->repository->method('getKnownCountries')->with(42)->willReturn(['US', 'CA']);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([]);

        $anomalies = $this->detector->detectAnomalies(42, [
            'country' => 'US',
            'timestamp' => time(),
        ]);

        $this->assertSame([], $anomalies);
    }

    public function testDetectAnomaliesNewCountry(): void
    {
        $this->repository->method('getKnownCountries')->with(42)->willReturn(['US', 'CA']);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([]);

        $anomalies = $this->detector->detectAnomalies(42, [
            'country' => 'RU',
            'timestamp' => time(),
        ]);

        $this->assertCount(1, $anomalies);
        $this->assertSame('new_country:RU', $anomalies[0]);
    }

    public function testDetectAnomaliesSkipsNewCountryForFirstLogin(): void
    {
        $this->repository->method('getKnownCountries')->with(42)->willReturn([]);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([]);

        $anomalies = $this->detector->detectAnomalies(42, [
            'country' => 'US',
            'timestamp' => time(),
        ]);

        $this->assertSame([], $anomalies);
    }

    public function testDetectAnomaliesImpossibleTravel(): void
    {
        $now = time();

        $this->repository->method('getKnownCountries')->with(42)->willReturn(['US', 'JP']);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([
            ['country' => 'US', 'timestamp' => $now - 600], // 10 minutes ago
        ]);

        $anomalies = $this->detector->detectAnomalies(42, [
            'country' => 'JP',
            'timestamp' => $now,
        ]);

        $this->assertCount(1, $anomalies);
        $this->assertStringContainsString('impossible_travel', $anomalies[0]);
        $this->assertStringContainsString('US->JP', $anomalies[0]);
    }

    public function testDetectAnomaliesNoImpossibleTravelWhenEnoughTime(): void
    {
        $now = time();

        $this->repository->method('getKnownCountries')->with(42)->willReturn(['US', 'JP']);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([
            ['country' => 'US', 'timestamp' => $now - 7200], // 2 hours ago
        ]);

        $anomalies = $this->detector->detectAnomalies(42, [
            'country' => 'JP',
            'timestamp' => $now,
        ]);

        $this->assertSame([], $anomalies);
    }

    public function testDetectAnomaliesSkipsUnknownCountry(): void
    {
        $this->repository->method('getKnownCountries')->with(42)->willReturn(['US']);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([]);

        $anomalies = $this->detector->detectAnomalies(42, [
            'country' => 'unknown',
            'timestamp' => time(),
        ]);

        $this->assertSame([], $anomalies);
    }

    public function testOnLoginRecordsAndDetects(): void
    {
        $this->detector->setResolvedUserId(42);
        $this->detector->setCountry('RU');

        $this->repository->method('getKnownCountries')->with(42)->willReturn(['US']);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([]);

        $this->logger->expects($this->once())->method('warning');
        $this->repository->expects($this->once())->method('recordLogin');

        $user = new \stdClass();
        $user->ID = 42;

        $this->detector->onLogin('admin', $user);
    }

    public function testOnLoginSkipsNullUser(): void
    {
        $this->detector->setResolvedUserId(null);

        $this->repository->expects($this->never())->method('recordLogin');

        $this->detector->onLogin('admin', null);
    }

    public function testOnLoginNoWarningWhenNoAnomalies(): void
    {
        $this->detector->setResolvedUserId(42);
        $this->detector->setCountry('US');

        $this->repository->method('getKnownCountries')->with(42)->willReturn(['US']);
        $this->repository->method('getLoginHistory')->with(42, 1)->willReturn([]);

        $this->logger->expects($this->never())->method('warning');
        $this->repository->expects($this->once())->method('recordLogin');

        $user = new \stdClass();
        $user->ID = 42;

        $this->detector->onLogin('admin', $user);
    }
}
