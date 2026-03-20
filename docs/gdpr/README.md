# GDPR Bundle

Le bundle `BackTo\Framework\Bundle\Gdpr` fournit un systeme complet de gestion du consentement cookies conforme au RGPD. Il inclut une banniere de consentement, un systeme de categories, un registre de scripts de tracking, le stockage du consentement par cookie, et des scripts predefinis pour les outils d'analyse et de marketing les plus courants.

## Tutorial

### Mettre en place la banniere de consentement

1. **Enregistrez l'extension GDPR** dans votre kernel :

```php
$kernel->addExtension(new GdprExtension());
```

2. **Definissez vos categories de consentement** en implementant `ConsentCategoryInterface` ou en utilisant l'entite `ConsentCategory` :

```php
<?php

declare(strict_types=1);

namespace App\Gdpr;

use BackTo\Framework\Bundle\Gdpr\Entity\ConsentCategory;

final class ConsentCategories
{
    public static function necessary(): ConsentCategory
    {
        return new ConsentCategory(
            key: 'necessary',
            label: 'Cookies necessaires',
            description: 'Indispensables au fonctionnement du site.',
            required: true,
        );
    }

    public static function analytics(): ConsentCategory
    {
        return new ConsentCategory(
            key: 'analytics',
            label: 'Cookies analytiques',
            description: 'Nous aident a comprendre comment vous utilisez le site.',
        );
    }

    public static function marketing(): ConsentCategory
    {
        return new ConsentCategory(
            key: 'marketing',
            label: 'Cookies marketing',
            description: 'Utilises pour le suivi publicitaire.',
        );
    }
}
```

3. **Enregistrez les categories comme services** tagges `wordpress.consent_category` (auto-configure si elles implementent `ConsentCategoryInterface`).

4. **Ajoutez des scripts de tracking** en utilisant les presets ou des scripts personnalises :

```php
<?php

use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAnalyticsScript;
use BackTo\Framework\Bundle\Gdpr\Preset\GtagLoaderScript;

// Dans votre configuration de services :
$services->set(GtagLoaderScript::class)
    ->args(['G-XXXXXXXXXX']);

$services->set(GoogleAnalyticsScript::class)
    ->args(['G-XXXXXXXXXX']);
```

5. La banniere s'affiche automatiquement au premier visit (hook `wp_footer`). Les scripts ne sont charges que si l'utilisateur a donne son consentement pour la categorie correspondante.

## How-to

### Ajouter Google Tag Manager

```php
use BackTo\Framework\Bundle\Gdpr\Preset\GoogleTagManagerScript;

$services->set(GoogleTagManagerScript::class)
    ->args(['GTM-XXXXXXX']);
```

Le script est inline, dans le `<head>`, avec une priorite de 1 (charge en premier). Categorie : `analytics`.

### Ajouter Google Ads

```php
use BackTo\Framework\Bundle\Gdpr\Preset\GoogleAdsScript;

$services->set(GoogleAdsScript::class)
    ->args(['AW-XXXXXXXXX']);
```

Script inline dans le `<head>`, priorite 5. Categorie : `marketing`.

### Ajouter HubSpot

```php
use BackTo\Framework\Bundle\Gdpr\Preset\HubSpotScript;

$services->set(HubSpotScript::class)
    ->args(['12345678']);
```

Script externe charge dans le `<footer>`, priorite 10. Categorie : `marketing`.

### Ajouter Hotjar

```php
use BackTo\Framework\Bundle\Gdpr\Preset\HotjarScript;

$services->set(HotjarScript::class)
    ->args(['1234567']);
```

Script inline dans le `<head>`, priorite 10. Categorie : `analytics`.

### Creer un script de tracking personnalise

Implementez `TrackingScriptInterface` ou utilisez l'entite `TrackingScript` :

```php
use BackTo\Framework\Bundle\Gdpr\Entity\TrackingScript;

$services->set('custom_pixel', TrackingScript::class)
    ->args([
        'facebook-pixel',       // handle
        'marketing',            // categoryKey
        '!function(f,b,e,...)', // source (code inline)
        true,                   // inline
        'head',                 // location ('head' ou 'footer')
        5,                      // priority
    ]);
```

### Verifier le consentement cote serveur

Injectez `ConsentStorageInterface` dans votre service :

```php
public function __construct(
    private readonly ConsentStorageInterface $consentStorage,
) {}

public function maMethode(): void
{
    if ($this->consentStorage->hasConsent('analytics')) {
        // L'utilisateur a accepte les cookies analytiques.
    }
}
```

## Reference

### Interfaces

#### `ConsentCategoryInterface`

Definit une categorie de consentement.

| Methode | Retour | Description |
|---|---|---|
| `getKey()` | `string` | Cle unique (ex. `analytics`, `marketing`) |
| `getLabel()` | `string` | Nom affiche dans la banniere |
| `getDescription()` | `string` | Description affichee dans la banniere |
| `isRequired()` | `bool` | `true` si la categorie ne peut pas etre desactivee |

#### `TrackingScriptInterface`

Definit un script de tracking.

| Methode | Retour | Description |
|---|---|---|
| `getHandle()` | `string` | Identifiant unique du script |
| `getCategoryKey()` | `string` | Cle de la categorie de consentement associee |
| `getSource()` | `string` | URL du script ou code inline |
| `isInline()` | `bool` | `true` = code inline, `false` = script externe (attribut `src`) |
| `getLocation()` | `string` | `'head'` ou `'footer'` |
| `getPriority()` | `int` | Ordre de chargement (plus petit = plus tot) |

#### `ConsentStorageInterface`

Port pour le stockage du consentement.

| Methode | Retour | Description |
|---|---|---|
| `getConsent()` | `array<string, bool>` | Retourne l'etat de consentement par categorie |
| `hasConsent(string $categoryKey)` | `bool` | Verifie le consentement pour une categorie |
| `isConsentGiven()` | `bool` | `true` si l'utilisateur a fait un choix |

#### `ConsentCategoryRegistryInterface`

| Methode | Description |
|---|---|
| `add(ConsentCategoryInterface $category)` | Ajoute une categorie |
| `getCategories()` | Retourne toutes les categories |
| `get(string $key)` | Retourne une categorie par sa cle |

#### `TrackingScriptRegistryInterface`

| Methode | Description |
|---|---|
| `add(TrackingScriptInterface $script)` | Ajoute un script |
| `getScripts()` | Retourne tous les scripts |
| `getScriptsByCategory(string $categoryKey)` | Filtre les scripts par categorie |

### Entites

#### `ConsentCategory`

Implementation concrete de `ConsentCategoryInterface`.

```php
new ConsentCategory(
    key: 'analytics',
    label: 'Analytique',
    description: 'Cookies de mesure d\'audience.',
    required: false,
);
```

#### `TrackingScript`

Implementation concrete de `TrackingScriptInterface`. Valide les parametres au constructeur :
- `$handle` et `$source` ne peuvent pas etre vides.
- `$location` doit etre `'head'` ou `'footer'`.

### Scripts predefinis (Preset)

| Classe | Handle | Categorie | Type | Location | Priorite | Parametre |
|---|---|---|---|---|---|---|
| `GtagLoaderScript` | `gtag-loader` | `analytics`* | Externe | `head` | 4 | `$trackingId` |
| `GoogleAnalyticsScript` | `google-analytics` | `analytics` | Inline | `head` | 5 | `$measurementId` |
| `GoogleTagManagerScript` | `google-tag-manager` | `analytics` | Inline | `head` | 1 | `$containerId` |
| `GoogleAdsScript` | `google-ads` | `marketing` | Inline | `head` | 5 | `$conversionId` |
| `HubSpotScript` | `hubspot` | `marketing` | Externe | `footer` | 10 | `$portalId` |
| `HotjarScript` | `hotjar` | `analytics` | Inline | `head` | 10 | `$siteId` |

\* `GtagLoaderScript` accepte un second parametre `$categoryKey` pour changer la categorie.

### Classes principales

| Classe | Description |
|---|---|
| `ConsentCategoryRegistry` | Registre des categories (indexe par cle) |
| `TrackingScriptRegistry` | Registre des scripts |
| `ConsentBanner` | Genere le HTML complet de la banniere |
| `ConsentBannerRenderer` | Genere les checkboxes, le CSS et le JavaScript |
| `RegisterGdpr` | Orchestre le rendu : scripts head/footer + banniere |
| `CookieConsentStorage` | Lit le cookie `gdpr_consent` (JSON, max 4KB) |

## Explanation

### Flux de consentement

1. **Premier acces** : aucun cookie `gdpr_consent` n'existe. `CookieConsentStorage::isConsentGiven()` retourne `false`. La banniere s'affiche automatiquement.
2. **Choix de l'utilisateur** : le JavaScript cote client ecrit un cookie `gdpr_consent` contenant un JSON `{"analytics": true, "marketing": false}`, puis recharge la page.
3. **Visites suivantes** : `RegisterGdpr` lit le consentement via `CookieConsentStorage` et ne charge que les scripts dont la categorie a ete acceptee (ou qui appartiennent a une categorie `required`).

### Architecture

Le bundle suit le pattern ports & adaptateurs :

- **Port** : `ConsentStorageInterface` (abstrait le mecanisme de stockage).
- **Adaptateur** : `CookieConsentStorage` (utilise `$_COOKIE`).

Les registres (`ConsentCategoryRegistry`, `TrackingScriptRegistry`) sont remplis automatiquement par les compiler passes `RegisterConsentCategoryPass` et `RegisterTrackingScriptPass`, qui collectent les services tagges `wordpress.consent_category` et `wordpress.tracking_script`.

### Separation rendu / logique

`ConsentBanner` orchestre la construction du HTML en delegant a `ConsentBannerRenderer` (SRP). Le renderer est responsable des checkboxes, du CSS et du JavaScript. Cette separation permet de remplacer le rendu sans toucher a la logique de banniere.

### Securite du cookie

`CookieConsentStorage` applique plusieurs protections :
- Verification que la valeur est une chaine.
- Limite de taille a 4 096 octets (limite navigateur).
- Decodage JSON avec validation du type retourne.
- Le cookie est ecrit avec `SameSite=Lax` cote JavaScript.
