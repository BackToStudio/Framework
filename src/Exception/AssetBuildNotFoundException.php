<?php

declare(strict_types=1);

namespace BackTo\Framework\Exception;

class AssetBuildNotFoundException extends FrameworkException
{
    public static function forDirectory(string $directory): self
    {
        return new self(sprintf(
            'Build folder not found in "%s". Run "npm install" and "npm run build" first.',
            $directory
        ));
    }
}
