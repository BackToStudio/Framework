<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class DisableFileEditor implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'disable_file_editor';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'disableFileEditing']);
    }

    public function disableFileEditing(): void
    {
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }

    public function isFileEditingDisabled(): bool
    {
        return defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT === true;
    }
}
