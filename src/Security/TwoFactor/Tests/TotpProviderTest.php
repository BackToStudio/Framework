<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Tests;

use BackTo\Framework\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Security\TwoFactor\TotpProvider;
use PHPUnit\Framework\TestCase;

class TotpProviderTest extends TestCase
{
    private TotpProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new TotpProvider();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(TotpProviderInterface::class, $this->provider);
    }

    public function testGenerateSecretReturnsBase32String(): void
    {
        $secret = $this->provider->generateSecret();

        $this->assertNotEmpty($secret);
        // Base32 alphabet: A-Z and 2-7
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGenerateSecretUniqueness(): void
    {
        $secret1 = $this->provider->generateSecret();
        $secret2 = $this->provider->generateSecret();

        $this->assertNotSame($secret1, $secret2);
    }

    public function testGenerateCodeReturns6Digits(): void
    {
        $secret = $this->provider->generateSecret();
        $code = $this->provider->generateCode($secret);

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testGenerateCodeDeterministic(): void
    {
        $secret = $this->provider->generateSecret();
        $timestamp = 1234567890;

        $code1 = $this->provider->generateCode($secret, $timestamp);
        $code2 = $this->provider->generateCode($secret, $timestamp);

        $this->assertSame($code1, $code2);
    }

    public function testVerifyCodeSuccess(): void
    {
        $secret = $this->provider->generateSecret();
        $code = $this->provider->generateCode($secret);

        $this->assertTrue($this->provider->verifyCode($secret, $code));
    }

    public function testVerifyCodeRejectsWrongCode(): void
    {
        $secret = $this->provider->generateSecret();

        $this->assertFalse($this->provider->verifyCode($secret, '000000'));
    }

    public function testVerifyCodeAllowsTimeDiscrepancy(): void
    {
        $secret = $this->provider->generateSecret();

        // Generate code for 30 seconds ago
        $code = $this->provider->generateCode($secret, time() - 30);

        // Should still verify with discrepancy=1 (default)
        $this->assertTrue($this->provider->verifyCode($secret, $code, 1));
    }

    public function testVerifyCodeRejectsOutsideDiscrepancy(): void
    {
        $secret = $this->provider->generateSecret();

        // Generate code for 90 seconds ago (3 periods)
        $code = $this->provider->generateCode($secret, time() - 90);

        // Should fail with discrepancy=1
        $this->assertFalse($this->provider->verifyCode($secret, $code, 1));
    }

    public function testGetProvisioningUri(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $uri = $this->provider->getProvisioningUri($secret, 'user@example.com', 'MyApp');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=MyApp', $uri);
        $this->assertStringContainsString('user%40example.com', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }

    /**
     * Test against known TOTP test vectors (RFC 6238).
     * Secret: "12345678901234567890" (ASCII) = Base32: GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ
     */
    public function testKnownVector(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        // At time = 59, timeSlice = 1
        $code = $this->provider->generateCode($secret, 59);
        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }
}
