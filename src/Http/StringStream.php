<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Minimal PSR-7 StreamInterface backed by an in-memory string.
 */
final class StringStream implements StreamInterface
{
    private ?string $content;
    private int $offset = 0;

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content ?? '';
    }

    public function close(): void
    {
        $this->content = null;
        $this->offset = 0;
    }

    public function detach()
    {
        $this->content = null;
        $this->offset = 0;

        return null;
    }

    public function getSize(): ?int
    {
        return $this->content !== null ? strlen($this->content) : null;
    }

    public function tell(): int
    {
        if ($this->content === null) {
            throw new \RuntimeException('Stream is detached');
        }

        return $this->offset;
    }

    public function eof(): bool
    {
        return $this->content === null || $this->offset >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return $this->content !== null;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->content === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $size = strlen($this->content);

        $this->offset = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->offset + $offset,
            SEEK_END => $size + $offset,
            default => throw new \RuntimeException("Invalid whence: {$whence}"),
        };
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->content !== null;
    }

    public function write(string $string): int
    {
        if ($this->content === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $this->content = substr($this->content, 0, $this->offset) . $string . substr($this->content, $this->offset + strlen($string));
        $length = strlen($string);
        $this->offset += $length;

        return $length;
    }

    public function isReadable(): bool
    {
        return $this->content !== null;
    }

    public function read(int $length): string
    {
        if ($this->content === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $data = substr($this->content, $this->offset, $length);
        $this->offset += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        if ($this->content === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $remaining = substr($this->content, $this->offset);
        $this->offset = strlen($this->content);

        return $remaining;
    }

    public function getMetadata(?string $key = null)
    {
        $metadata = [
            'mode' => 'r+',
            'seekable' => true,
        ];

        if ($key !== null) {
            return $metadata[$key] ?? null;
        }

        return $metadata;
    }
}
