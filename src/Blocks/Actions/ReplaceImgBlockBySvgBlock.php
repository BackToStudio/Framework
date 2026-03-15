<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks\Actions;

use BackTo\Framework\Assets\ReplaceImgTagBySvgTag;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

class ReplaceImgBlockBySvgBlock implements Hooks
{

    private ReplaceImgTagBySvgTag $replaceImgTagBySvgTag;

    private HookDispatcherInterface $hookDispatcher;

    public function __construct(ReplaceImgTagBySvgTag $replaceImgTagBySvgTag, HookDispatcherInterface $hookDispatcher)
    {
        $this->replaceImgTagBySvgTag = $replaceImgTagBySvgTag;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('render_block_core/image', [$this, 'replaceImgTag']);
    }

    function replaceImgTag(string $blockContent): string
    {
        return $this->replaceImgTagBySvgTag->fromHtml($blockContent);
    }
}
