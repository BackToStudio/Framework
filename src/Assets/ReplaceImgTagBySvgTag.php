<?php

namespace BackTo\Framework\Assets;


use Exception;

use function array_key_exists;
use function preg_match;
use function str_ends_with;
use function str_replace;

class ReplaceImgTagBySvgTag
{
    private SvgFactory $factory;

    public function __construct(SvgFactory $factory)
    {
        $this->factory = $factory;
    }

    function fromHtml(string $blockContent): string
    {
        preg_match("/<img.*src\s*=\s*[\"']([^\"']+)[\"'][^>]*>/", $blockContent, $matches);

        if (array_key_exists(1, $matches) && str_ends_with($matches[1], '.svg')) {
            try {
                $blockContent = str_replace($matches[0], $this->factory->getFromSrc($matches[1]), $blockContent);
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        return $blockContent;
    }
}
