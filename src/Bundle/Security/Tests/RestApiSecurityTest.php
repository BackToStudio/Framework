<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Hardening\RestApiSecurity;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control authentication and route checks.
 */
class TestableRestApiSecurity extends RestApiSecurity
{
    private bool $authenticated = false;
    private bool $publicRoute = false;
    private mixed $authError = null;

    public function setAuthenticated(bool $authenticated): void
    {
        $this->authenticated = $authenticated;
    }

    public function setPublicRoute(bool $publicRoute): void
    {
        $this->publicRoute = $publicRoute;
    }

    public function setAuthError(mixed $error): void
    {
        $this->authError = $error;
    }

    protected function isUserAuthenticated(): bool
    {
        return $this->authenticated;
    }

    protected function isPublicRoute(): bool
    {
        return $this->publicRoute;
    }

    protected function createAuthenticationError(): mixed
    {
        if ($this->authError !== null) {
            return $this->authError;
        }

        $error = new \stdClass();
        $error->code = 'rest_not_logged_in';

        return $error;
    }
}

class RestApiSecurityTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private RequestContextInterface $requestContext;
    private TestableRestApiSecurity $rule;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $userContext = $this->createMock(UserContextInterface::class);
        $siteContext = $this->createMock(SiteContextInterface::class);
        $this->rule = new TestableRestApiSecurity($this->dispatcher, $this->requestContext, $userContext, $siteContext);
    }

    public function testConstructorAcceptsAdditionalPublicPatterns(): void
    {
        $userContext = $this->createMock(UserContextInterface::class);
        $siteContext = $this->createMock(SiteContextInterface::class);
        $rule = new RestApiSecurity($this->dispatcher, $this->requestContext, $userContext, $siteContext, ['#^/custom/#']);
        $this->assertInstanceOf(RestApiSecurity::class, $rule);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->rule);
    }

    public function testGetName(): void
    {
        $this->assertSame('rest_api_security', $this->rule->getName());
    }

    public function testHooksRegistersFilters(): void
    {
        $this->dispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['rest_authentication_errors', 'rest_index']);
            });

        $this->rule->hooks();
    }

    public function testRequireAuthenticationPassesThroughExistingResult(): void
    {
        $existingError = new \stdClass();
        $result = $this->rule->requireAuthentication($existingError);

        $this->assertSame($existingError, $result);
    }

    public function testRequireAuthenticationAllowsPublicRoutes(): void
    {
        $this->rule->setPublicRoute(true);

        $result = $this->rule->requireAuthentication(null);
        $this->assertNull($result);
    }

    public function testRequireAuthenticationAllowsAuthenticatedUsers(): void
    {
        $this->rule->setAuthenticated(true);

        $result = $this->rule->requireAuthentication(null);
        $this->assertNull($result);
    }

    public function testRequireAuthenticationBlocksUnauthenticated(): void
    {
        $this->rule->setAuthenticated(false);
        $this->rule->setPublicRoute(false);

        $result = $this->rule->requireAuthentication(null);

        $this->assertIsObject($result);
        $this->assertSame('rest_not_logged_in', $result->code);
    }

    public function testFilterRestIndexPassesThroughForAuthenticated(): void
    {
        $this->rule->setAuthenticated(true);

        $response = new \stdClass();
        $result = $this->rule->filterRestIndex($response);

        $this->assertSame($response, $result);
    }

    public function testFilterRestIndexRemovesSensitiveData(): void
    {
        $this->rule->setAuthenticated(false);

        $response = new class {
            /** @var array<string, mixed> */
            private array $data = [
                'name' => 'Test API',
                'authentication' => ['type' => 'cookie'],
                'routes' => ['/wp/v2/posts' => []],
            ];

            /** @return array<string, mixed> */
            public function get_data(): array
            {
                return $this->data;
            }

            /** @param array<string, mixed> $data */
            public function set_data(array $data): void
            {
                $this->data = $data;
            }
        };

        $result = $this->rule->filterRestIndex($response);

        $data = $result->get_data();
        $this->assertArrayNotHasKey('authentication', $data);
        $this->assertArrayNotHasKey('routes', $data);
        $this->assertArrayHasKey('name', $data);
    }
}
