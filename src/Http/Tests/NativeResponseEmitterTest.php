<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Http\NativeResponseEmitter;
use PHPUnit\Framework\TestCase;

class NativeResponseEmitterTest extends TestCase
{
    public function testImplementsResponseEmitterInterface(): void
    {
        $emitter = new NativeResponseEmitter();
        $this->assertInstanceOf(ResponseEmitterInterface::class, $emitter);
    }

    public function testHeadersSentReturnsBool(): void
    {
        $emitter = new NativeResponseEmitter();
        // In CLI context, headers are never sent
        $result = $emitter->headersSent();
        $this->assertIsBool($result);
    }
}
