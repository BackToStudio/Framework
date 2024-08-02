<?php

namespace BackTo\Framework\Blocks\Actions;

use BackTo\Framework\Assets\ReplaceImgTagBySvgTag;
use BackTo\Framework\Contracts\Hooks;

use function add_action;

class ReplaceImgBlockBySvgBlock implements Hooks
{

    private ReplaceImgTagBySvgTag $replaceImgTagBySvgTag;

    public function __construct(ReplaceImgTagBySvgTag $replaceImgTagBySvgTag)
    {
        $this->replaceImgTagBySvgTag = $replaceImgTagBySvgTag;
    }

    public function hooks()
    {
        add_action('render_block_core/image', [$this, 'replaceImgTag']);
    }

    function replaceImgTag(string $blockContent): string
    {
        return $this->replaceImgTagBySvgTag->fromHtml($blockContent);
    }
}
