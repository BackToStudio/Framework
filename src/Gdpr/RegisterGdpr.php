<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Gdpr\Contracts\ConsentStorageInterface;
use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;

final class RegisterGdpr implements Hooks
{
    public function __construct(
        private readonly ConsentCategoryRegistry $categoryRegistry,
        private readonly TrackingScriptRegistry $scriptRegistry,
        private readonly ConsentStorageInterface $consentStorage,
        private readonly HookDispatcherInterface $hookDispatcher,
        private readonly ConsentBanner $consentBanner,
    ) {
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('wp_head', [$this, 'renderHeadScripts'], 1);
        $this->hookDispatcher->addAction('wp_footer', [$this, 'renderFooterScripts'], 50);
        $this->hookDispatcher->addAction('wp_footer', [$this, 'renderConsentBanner'], 100);
    }

    public function renderHeadScripts(): void
    {
        $this->renderScriptsForLocation('head');
    }

    public function renderFooterScripts(): void
    {
        $this->renderScriptsForLocation('footer');
    }

    public function renderConsentBanner(): void
    {
        echo $this->consentBanner->render();
    }

    private function renderScriptsForLocation(string $location): void
    {
        $scripts = $this->scriptRegistry->getScripts();

        usort($scripts, static fn (TrackingScriptInterface $a, TrackingScriptInterface $b): int => $a->getPriority() <=> $b->getPriority());

        foreach ($scripts as $script) {
            if ($script->getLocation() !== $location) {
                continue;
            }

            if (!$this->isScriptAllowed($script)) {
                continue;
            }

            $this->outputScript($script);
        }
    }

    private function isScriptAllowed(TrackingScriptInterface $script): bool
    {
        $category = $this->categoryRegistry->get($script->getCategoryKey());

        if ($category !== null && $category->isRequired()) {
            return true;
        }

        return $this->consentStorage->hasConsent($script->getCategoryKey());
    }

    private function outputScript(TrackingScriptInterface $script): void
    {
        if ($script->isInline()) {
            echo '<script>' . $script->getSource() . '</script>' . "\n";
        } else {
            echo '<script src="' . htmlspecialchars($script->getSource(), ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        }
    }
}
