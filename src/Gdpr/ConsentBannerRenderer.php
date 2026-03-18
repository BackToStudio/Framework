<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr;

use BackTo\Framework\Gdpr\Contracts\ConsentCategoryInterface;

/**
 * Renders the GDPR consent banner HTML, CSS, and JavaScript.
 *
 * Separated from ConsentBanner to respect Single Responsibility Principle.
 */
final class ConsentBannerRenderer
{
    /**
     * Render category checkboxes HTML.
     *
     * @param ConsentCategoryInterface[] $categories
     */
    public function renderCheckboxes(array $categories): string
    {
        $html = '';

        foreach ($categories as $category) {
            $key = htmlspecialchars($category->getKey(), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($category->getLabel(), ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars($category->getDescription(), ENT_QUOTES, 'UTF-8');
            $checked = $category->isRequired() ? ' checked' : '';
            $disabled = $category->isRequired() ? ' disabled' : '';

            $html .= <<<CHECKBOX
            <label class="gdpr-banner__category" for="gdpr-cat-{$key}">
                <input type="checkbox" id="gdpr-cat-{$key}" data-gdpr-category="{$key}"{$checked}{$disabled}>
                <span class="gdpr-banner__category-label">{$label}</span>
                <span class="gdpr-banner__category-desc">{$description}</span>
            </label>
            CHECKBOX;
        }

        return $html;
    }

    public function getCss(): string
    {
        return <<<'CSS'
        #gdpr-consent-banner {
            position: fixed;
            inset: 0;
            z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        .gdpr-banner__overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
        }
        .gdpr-banner__dialog {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            box-shadow: 0 -2px 16px rgba(0, 0, 0, 0.15);
            z-index: 1;
            max-height: 90vh;
            overflow-y: auto;
        }
        .gdpr-banner__content {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }
        .gdpr-banner__title {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .gdpr-banner__text {
            margin: 0 0 16px;
            color: #555;
        }
        .gdpr-banner__categories {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
        }
        .gdpr-banner__category {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 8px 12px;
            background: #f7f7f7;
            border-radius: 6px;
            cursor: pointer;
        }
        .gdpr-banner__category input {
            margin-top: 3px;
            flex-shrink: 0;
        }
        .gdpr-banner__category-label {
            font-weight: 500;
            color: #1a1a1a;
        }
        .gdpr-banner__category-desc {
            color: #777;
            font-size: 13px;
        }
        .gdpr-banner__actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .gdpr-banner__btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .gdpr-banner__btn:hover {
            opacity: 0.85;
        }
        .gdpr-banner__btn--accept {
            background: #2563eb;
            color: #fff;
        }
        .gdpr-banner__btn--reject {
            background: #e5e7eb;
            color: #374151;
        }
        .gdpr-banner__btn--save {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .gdpr-toggle {
            position: fixed;
            bottom: 16px;
            left: 16px;
            z-index: 999998;
            padding: 8px 16px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .gdpr-toggle:hover {
            opacity: 0.85;
        }
        CSS;
    }

    public function getJavaScript(string $categoriesJson, string $consentGivenJs, string $currentConsentJson): string
    {
        return <<<JS
        (function() {
            var COOKIE_NAME = 'gdpr_consent';
            var COOKIE_DAYS = 365;
            var categories = {$categoriesJson};
            var consentGiven = {$consentGivenJs};
            var currentConsent = {$currentConsentJson};

            var banner = document.getElementById('gdpr-consent-banner');
            var toggle = document.getElementById('gdpr-consent-toggle');

            function setCookie(name, value, days) {
                var expires = new Date(Date.now() + days * 864e5).toUTCString();
                document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + expires + ';path=/;SameSite=Lax';
            }

            function saveConsent(consent) {
                setCookie(COOKIE_NAME, JSON.stringify(consent), COOKIE_DAYS);
                window.location.reload();
            }

            function showBanner() {
                banner.style.display = 'block';
                toggle.style.display = 'none';
            }

            function hideBanner() {
                banner.style.display = 'none';
                toggle.style.display = 'block';
            }

            function getCheckboxConsent() {
                var consent = {};
                for (var i = 0; i < categories.length; i++) {
                    var key = categories[i].key;
                    var checkbox = document.getElementById('gdpr-cat-' + key);
                    consent[key] = checkbox ? checkbox.checked : categories[i].required;
                }
                return consent;
            }

            function initCheckboxes() {
                for (var i = 0; i < categories.length; i++) {
                    var key = categories[i].key;
                    var checkbox = document.getElementById('gdpr-cat-' + key);
                    if (checkbox && consentGiven && currentConsent[key] !== undefined) {
                        checkbox.checked = !!currentConsent[key];
                    }
                }
            }

            banner.addEventListener('click', function(e) {
                var action = e.target.getAttribute('data-gdpr-action');
                if (!action) return;

                if (action === 'accept') {
                    var consent = {};
                    for (var i = 0; i < categories.length; i++) {
                        consent[categories[i].key] = true;
                    }
                    saveConsent(consent);
                } else if (action === 'reject') {
                    var consent = {};
                    for (var i = 0; i < categories.length; i++) {
                        consent[categories[i].key] = !!categories[i].required;
                    }
                    saveConsent(consent);
                } else if (action === 'save') {
                    saveConsent(getCheckboxConsent());
                }
            });

            toggle.addEventListener('click', function() {
                showBanner();
            });

            initCheckboxes();

            if (!consentGiven) {
                showBanner();
            } else {
                hideBanner();
            }
        })();
        JS;
    }
}
