<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Http\StringStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class StringStreamTest extends TestCase
{
    public function testImplementsStreamInterface(): void
    {
        $this->assertInstanceOf(StreamInterface::class, new StringStream());
    }

    public function testToString(): void
    {
        $this->assertSame('hello', (string) new StringStream('hello'));
    }

    public function testGetSize(): void
    {
        $this->assertSame(5, (new StringStream('hello'))->getSize());
        $this->assertSame(0, (new StringStream(''))->getSize());
    }

    public function testReadAndTell(): void
    {
        $stream = new StringStream('hello world');
        $this->assertSame(0, $stream->tell());

        $this->assertSame('hello', $stream->read(5));
        $this->assertSame(5, $stream->tell());

        $this->assertSame(' world', $stream->read(6));
        $this->assertTrue($stream->eof());
    }

    public function testSeekAndRewind(): void
    {
        $stream = new StringStream('hello');
        $stream->seek(3);
        $this->assertSame(3, $stream->tell());

        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function testGetContents(): void
    {
        $stream = new StringStream('hello world');
        $stream->read(6);
        $this->assertSame('world', $stream->getContents());
    }

    public function testWrite(): void
    {
        $stream = new StringStream('hello');
        $stream->seek(5);
        $stream->write(' world');
        $this->assertSame('hello world', (string) $stream);
    }

    public function testIsReadableWritableSeekable(): void
    {
        $stream = new StringStream('test');
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
    }

    public function testDetachMakesStreamUnusable(): void
    {
        $stream = new StringStream('test');
        $stream->detach();

        $this->assertNull($stream->getSize());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());
        $this->assertSame('', (string) $stream);
    }

    public function testCloseDetachesStream(): void
    {
        $stream = new StringStream('test');
        $stream->close();

        $this->assertNull($stream->getSize());
    }

    public function testEofOnEmptyStream(): void
    {
        $this->assertTrue((new StringStream(''))->eof());
    }

    public function testGetMetadata(): void
    {
        $stream = new StringStream('test');
        $this->assertIsArray($stream->getMetadata());
        $this->assertTrue($stream->getMetadata('seekable'));
        $this->assertNull($stream->getMetadata('nonexistent'));
    }
}
