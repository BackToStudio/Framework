<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\Infrastructure\WordPressLogger;
use PHPUnit\Framework\TestCase;

class WordPressLoggerTest extends TestCase
{
    public function testLogInterpolatesContext(): void
    {
        $logger = new WordPressLogger('debug', 'Test');
        $output = [];

        \set_error_handler(static function (int $errno, string $errstr) use (&$output): bool {
            $output[] = $errstr;
            return true;
        });

        try {
            $logger->error('User {user} failed login', ['user' => 'alice']);
        } finally {
            \restore_error_handler();
        }

        $this->assertCount(1, $output);
        $this->assertStringContainsString('User alice failed login', $output[0]);
        $this->assertStringContainsString('Test.ERROR', $output[0]);
    }

    public function testMinLevelFiltersMessages(): void
    {
        $logger = new WordPressLogger('error', 'Test');
        $output = [];

        \set_error_handler(static function (int $errno, string $errstr) use (&$output): bool {
            $output[] = $errstr;
            return true;
        });

        try {
            $logger->debug('should be filtered');
            $logger->info('should be filtered');
            $logger->warning('should be filtered');
            $logger->error('should pass');
            $logger->critical('should pass');
        } finally {
            \restore_error_handler();
        }

        $this->assertCount(2, $output);
    }

    public function testAppendsExtraContextAsJson(): void
    {
        $logger = new WordPressLogger('debug', 'Test');
        $output = [];

        \set_error_handler(static function (int $errno, string $errstr) use (&$output): bool {
            $output[] = $errstr;
            return true;
        });

        try {
            $logger->info('Event occurred', ['type' => 'login', 'ip' => '127.0.0.1']);
        } finally {
            \restore_error_handler();
        }

        $this->assertStringContainsString('"type":"login"', $output[0]);
    }

    public function testAllLevelMethodsWork(): void
    {
        $logger = new WordPressLogger('debug');
        $count = 0;

        \set_error_handler(static function () use (&$count): bool {
            $count++;
            return true;
        });

        try {
            $logger->emergency('test');
            $logger->alert('test');
            $logger->critical('test');
            $logger->error('test');
            $logger->warning('test');
            $logger->notice('test');
            $logger->info('test');
            $logger->debug('test');
        } finally {
            \restore_error_handler();
        }

        $this->assertSame(8, $count);
    }
}
