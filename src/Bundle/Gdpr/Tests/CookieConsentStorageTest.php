<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\Tests;

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Bundle\Gdpr\Infrastructure\CookieConsentStorage;
use PHPUnit\Framework\TestCase;

class CookieConsentStorageTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_COOKIE['gdpr_consent']);
    }

    public function testImplementsInterface(): void
    {
        $storage = new CookieConsentStorage();
        $this->assertInstanceOf(ConsentStorageInterface::class, $storage);
    }

    public function testGetConsentWithoutCookie(): void
    {
        $storage = new CookieConsentStorage();
        $this->assertSame([], $storage->getConsent());
    }

    public function testIsConsentGivenWithoutCookie(): void
    {
        $storage = new CookieConsentStorage();
        $this->assertFalse($storage->isConsentGiven());
    }

    public function testGetConsentWithValidCookie(): void
    {
        $_COOKIE['gdpr_consent'] = json_encode(['analytics' => true, 'marketing' => false]);

        $storage = new CookieConsentStorage();
        $consent = $storage->getConsent();

        $this->assertTrue($consent['analytics']);
        $this->assertFalse($consent['marketing']);
    }

    public function testHasConsentTrue(): void
    {
        $_COOKIE['gdpr_consent'] = json_encode(['analytics' => true]);

        $storage = new CookieConsentStorage();
        $this->assertTrue($storage->hasConsent('analytics'));
    }

    public function testHasConsentFalse(): void
    {
        $_COOKIE['gdpr_consent'] = json_encode(['analytics' => false]);

        $storage = new CookieConsentStorage();
        $this->assertFalse($storage->hasConsent('analytics'));
    }

    public function testHasConsentForMissingKey(): void
    {
        $_COOKIE['gdpr_consent'] = json_encode(['analytics' => true]);

        $storage = new CookieConsentStorage();
        $this->assertFalse($storage->hasConsent('marketing'));
    }

    public function testIsConsentGivenWithCookie(): void
    {
        $_COOKIE['gdpr_consent'] = json_encode(['analytics' => true]);

        $storage = new CookieConsentStorage();
        $this->assertTrue($storage->isConsentGiven());
    }

    public function testGetConsentWithInvalidJson(): void
    {
        $_COOKIE['gdpr_consent'] = 'invalid-json';

        $storage = new CookieConsentStorage();
        $this->assertSame([], $storage->getConsent());
    }

    public function testGetConsentRejectsOversizedCookie(): void
    {
        // Simulate a maliciously large cookie (> 4KB limit)
        $_COOKIE['gdpr_consent'] = str_repeat('x', 5000);

        $storage = new CookieConsentStorage();
        $this->assertSame([], $storage->getConsent());
    }

    public function testGetConsentAcceptsCookieWithinSizeLimit(): void
    {
        $_COOKIE['gdpr_consent'] = json_encode(['analytics' => true]);

        $storage = new CookieConsentStorage();
        $consent = $storage->getConsent();
        $this->assertTrue($consent['analytics']);
    }

    public function testHasConsentReturnsFalseForOversizedCookie(): void
    {
        $_COOKIE['gdpr_consent'] = str_repeat('x', 5000);

        $storage = new CookieConsentStorage();
        $this->assertFalse($storage->hasConsent('analytics'));
    }
}
