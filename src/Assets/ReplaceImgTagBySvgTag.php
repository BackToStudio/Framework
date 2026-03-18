<?php

declare(strict_types=1);

namespace BackTo\Framework\Assets;


use BackTo\Framework\Observability\Contracts\LoggerInterface;
use Exception;

use function array_key_exists;
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

    function fromHtml(string $blockContent): string
    {
        preg_match("/<img.*src\s*=\s*[\"']([^\"']+)[\"'][^>]*>/", $blockContent, $matches);

        if (array_key_exists(1, $matches) && str_ends_with($matches[1], '.svg')) {
            try {
                $blockContent = str_replace($matches[0], $this->factory->getFromSrc($matches[1]), $blockContent);
            } catch (Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }

        return $blockContent;
    }
}
