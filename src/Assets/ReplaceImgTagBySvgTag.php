<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;


use BackTo\Framework\Contracts\LoggerInterface;
use Exception;

use function preg_match;
use function str_ends_with;
use function str_replace;

final class ReplaceImgTagBySvgTag
{
    private readonly SvgFactory $factory;
    private readonly LoggerInterface $logger;

    public function __construct(SvgFactory $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function fromHtml(string $blockContent): string
    {
        $match = $this->findSvgImgTag($blockContent);

        if ($match === null) {
            return $blockContent;
        }

        try {
            $blockContent = str_replace($match['tag'], $this->factory->getFromSrc($match['src']), $blockContent);
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        return $blockContent;
    }

    /**
     * Detect the first <img> tag pointing to an SVG source.
     *
     * @return array{tag: string, src: string}|null
     */
    private function findSvgImgTag(string $html): ?array
    {
        if (preg_match("/<img.*src\s*=\s*[\"']([^\"']+)[\"'][^>]*>/", $html, $matches) !== 1) {
            return null;
        }

        if (!str_ends_with($matches[1], '.svg')) {
            return null;
        }

        return ['tag' => $matches[0], 'src' => $matches[1]];
    }
}
