<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr;

use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Bundle\Gdpr\Contracts\ConsentStorageInterface;

final class ConsentBanner
{
    public function __construct(
        private readonly ConsentCategoryRegistry $categoryRegistry,
        private readonly ConsentStorageInterface $consentStorage,
        private readonly ConsentBannerRenderer $renderer,
        private readonly BannerDataBuilder $dataBuilder,
    ) {
    }

    public function render(): string
    {
        $categories = $this->categoryRegistry->getCategories();

        if ($categories === []) {
            return '';
        }

        $categoriesJson = $this->dataBuilder->getCategoriesJson();
        $consentGivenJs = $this->dataBuilder->getConsentGivenJs();
        $currentConsent = $this->dataBuilder->getCurrentConsentJson();

        $checkboxesHtml = $this->renderer->renderCheckboxes($categories);
        $css = $this->renderer->getCss();
        $js = $this->renderer->getJavaScript($categoriesJson, $consentGivenJs, $currentConsent);

        return <<<HTML
        <div id="gdpr-consent-banner" style="display:none;">
            <style>{$css}</style>
            <div class="gdpr-banner__overlay"></div>
            <div class="gdpr-banner__dialog" role="dialog" aria-label="Gestion des cookies" aria-modal="true">
                <div class="gdpr-banner__content">
                    <h2 class="gdpr-banner__title">Gestion des cookies</h2>
                    <p class="gdpr-banner__text">
                        Nous utilisons des cookies pour personnaliser votre experience et analyser notre trafic.
                        Vous pouvez choisir les categories de cookies que vous acceptez.
                    </p>
                    <div class="gdpr-banner__categories">
                        {$checkboxesHtml}
                    </div>
                    <div class="gdpr-banner__actions">
                        <button type="button" class="gdpr-banner__btn gdpr-banner__btn--reject" data-gdpr-action="reject">
                            Tout refuser
                        </button>
                        <button type="button" class="gdpr-banner__btn gdpr-banner__btn--save" data-gdpr-action="save">
                            Enregistrer mes choix
                        </button>
                        <button type="button" class="gdpr-banner__btn gdpr-banner__btn--accept" data-gdpr-action="accept">
                            Tout accepter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" id="gdpr-consent-toggle" class="gdpr-toggle" style="display:none;" aria-label="Gerer les cookies">
            Gerer les cookies
        </button>
        <script>
        {$js}
        </script>
        HTML;
    }
}
