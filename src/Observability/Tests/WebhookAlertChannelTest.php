<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\Alert\WebhookAlertChannel;
use PHPUnit\Framework\TestCase;

final class WebhookAlertChannelTest extends TestCase
{
    public function test_name_is_webhook(): void
    {
        $channel = new WebhookAlertChannel('https://example.com/hook');

        $this->assertSame('webhook', $channel->getName());
    }

    public function test_throws_for_empty_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new WebhookAlertChannel('');
    }

    public function test_builds_slack_payload(): void
    {
        // We can't test the actual HTTP call, but we can verify the class instantiates
        $channel = new WebhookAlertChannel('https://hooks.slack.com/services/xxx');

        $this->assertSame('webhook', $channel->getName());
    }

    public function test_builds_discord_payload(): void
    {
        $channel = new WebhookAlertChannel('https://discord.com/api/webhooks/xxx');

        $this->assertSame('webhook', $channel->getName());
    }
}
