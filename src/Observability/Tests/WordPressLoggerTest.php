<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\Infrastructure\WordPressLogger;
use PHPUnit\Framework\TestCase;

class WordPressLoggerTest extends TestCase
{
    private string $logFile;
    private string|false $originalErrorLog;

    protected function setUp(): void
    {
        $this->logFile = tempnam(sys_get_temp_dir(), 'btf_log_');
        $this->originalErrorLog = ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        if ($this->originalErrorLog !== false) {
            ini_set('error_log', $this->originalErrorLog);
        }
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    private function getLogOutput(): string
    {
        return file_exists($this->logFile) ? file_get_contents($this->logFile) : '';
    }

    private function getLogLines(): array
    {
        $content = $this->getLogOutput();

        return $content !== '' ? array_filter(explode("\n", trim($content))) : [];
    }

    public function testLogInterpolatesContext(): void
    {
        $logger = new WordPressLogger('debug', 'Test');

        $logger->error('User {user} failed login', ['user' => 'alice']);

        $output = $this->getLogOutput();
        $this->assertStringContainsString('User alice failed login', $output);
        $this->assertStringContainsString('Test.ERROR', $output);
    }

    public function testMinLevelFiltersMessages(): void
    {
        $logger = new WordPressLogger('error', 'Test');

        $logger->debug('should be filtered');
        $logger->info('should be filtered');
        $logger->warning('should be filtered');
        $logger->error('should pass');
        $logger->critical('should pass');

        $lines = $this->getLogLines();
        $this->assertCount(2, $lines);
    }

    public function testAppendsExtraContextAsJson(): void
    {
        $logger = new WordPressLogger('debug', 'Test');

        $logger->info('Event occurred', ['type' => 'login', 'ip' => '127.0.0.1']);

        $output = $this->getLogOutput();
        $this->assertStringContainsString('"type":"login"', $output);
    }

    public function testAllLevelMethodsWork(): void
    {
        $logger = new WordPressLogger('debug');

        $logger->emergency('test');
        $logger->alert('test');
        $logger->critical('test');
        $logger->error('test');
        $logger->warning('test');
        $logger->notice('test');
        $logger->info('test');
        $logger->debug('test');

        $lines = $this->getLogLines();
        $this->assertCount(8, $lines);
    }

    public function testImplementsPsr3Interface(): void
    {
        $logger = new WordPressLogger();
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    public function testAcceptsStringableMessage(): void
    {
        $logger = new WordPressLogger('debug', 'Test');

        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable message';
            }
        };

        $logger->info($stringable);

        $output = $this->getLogOutput();
        $this->assertStringContainsString('stringable message', $output);
    }
}
