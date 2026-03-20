<?php

declare(strict_types=1);

namespace BackTo\Framework\Tests\Integration\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Hardening\AdminUrlObfuscation;
use BackTo\Framework\Bundle\Security\Contracts\ClientIpResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: Admin URL Obfuscation
 *
 * USE CASE VIDEO: "L'utilisateur tente de se connecter via /wp-admin/
 * alors que l'URL de login a ete configuree sur un slug personnalise."
 *
 * SCENARIO NARRATIF (storyboard video) :
 * ======================================
 *
 * SCENE 1 - Configuration initiale
 *   L'administrateur configure le slug de login personnalise "/acces-securise"
 *   a la place du traditionnel /wp-login.php et /wp-admin/
 *
 * SCENE 2 - Un visiteur malveillant tente /wp-login.php
 *   -> Le systeme detecte la tentative, log l'IP, renvoie une 404
 *   -> L'attaquant ne sait pas que le site est sous WordPress
 *
 * SCENE 3 - Un visiteur malveillant tente /wp-admin/
 *   -> Meme comportement : 404, aucun indice visible
 *
 * SCENE 4 - L'utilisateur legitime utilise /acces-securise
 *   -> Le systeme reconnait le slug, redirige vers la page de login
 *
 * SCENE 5 - Les URLs de login/logout sont automatiquement reecrites
 *   -> login_url(), logout_url(), site_url() pointent vers /acces-securise
 *
 * SCENE 6 - Un utilisateur deja connecte accede a /wp-admin/ normalement
 *   -> Pas de blocage, il est authentifie
 *
 * SCENE 7 - Le slug n'est pas configure -> aucun hook n'est enregistre
 *   -> Comportement WordPress par defaut preserve
 */
class AdminUrlObfuscationIntegrationTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private RequestContextInterface $requestContext;
    private LoggerInterface $logger;
    private ClientIpResolverInterface $ipResolver;

    /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
    private array $logEntries = [];

    /** @var array<int, array{event: string, callback: callable, priority: int}> */
    private array $registeredHooks = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->logEntries = [];
        $this->registeredHooks = [];

        // -- Mock HookDispatcher: captures registered hooks
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->hookDispatcher->method('addAction')->willReturnCallback(
            function (string $event, callable $callback, int $priority = 10): void {
                $this->registeredHooks[] = [
                    'event' => $event,
                    'callback' => $callback,
                    'priority' => $priority,
                ];
            }
        );
        $this->hookDispatcher->method('addFilter')->willReturnCallback(
            function (string $event, callable $callback, int $priority = 10): void {
                $this->registeredHooks[] = [
                    'event' => $event,
                    'callback' => $callback,
                    'priority' => $priority,
                ];
            }
        );

        // -- Mock RequestContext: configurable URI per test
        $this->requestContext = $this->createMock(RequestContextInterface::class);

        // -- Mock Logger: captures log entries for assertion
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->method('warning')->willReturnCallback(
            function (string $message, array $context = []): void {
                $this->logEntries[] = [
                    'level' => 'warning',
                    'message' => $message,
                    'context' => $context,
                ];
            }
        );

        // -- Mock IpResolver
        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('203.0.113.42');
    }

    // =================================================================
    // SCENE 1 : Configuration initiale du slug personnalise
    // =================================================================

    /**
     * @test
     * @group scene1
     *
     * VIDEO: L'admin ouvre le panneau de configuration securite,
     * saisit "acces-securise" comme slug de login, et sauvegarde.
     */
    public function scene1_admin_configures_custom_login_slug(): void
    {
        $obfuscation = $this->createObfuscation();

        // Le slug est vide par defaut
        $this->assertSame('', $obfuscation->getLoginSlug());

        // L'admin configure le slug
        $obfuscation->setLoginSlug('acces-securise');

        $this->assertSame('acces-securise', $obfuscation->getLoginSlug());
        $this->assertSame('admin_url_obfuscation', $obfuscation->getName());
    }

    /**
     * @test
     * @group scene1
     *
     * VIDEO: Le systeme confirme que les hooks de securite sont actifs.
     * On voit les 5 hooks s'enregistrer dans le panneau de debug.
     */
    public function scene1_hooks_are_registered_when_slug_is_set(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('acces-securise');
        $obfuscation->hooks();

        $hookEvents = array_column($this->registeredHooks, 'event');

        $this->assertContains('init', $hookEvents, 'Hook "init" doit intercepter le slug personnalise');
        $this->assertContains('wp_loaded', $hookEvents, 'Hook "wp_loaded" doit bloquer /wp-login.php');
        $this->assertContains('login_url', $hookEvents, 'Filtre "login_url" doit reecrire les URLs');
        $this->assertContains('logout_url', $hookEvents, 'Filtre "logout_url" doit reecrire les URLs');
        $this->assertContains('site_url', $hookEvents, 'Filtre "site_url" doit reecrire les URLs');

        $this->assertCount(5, $this->registeredHooks, '5 hooks doivent etre enregistres');
    }

    // =================================================================
    // SCENE 2 : Un visiteur malveillant tente /wp-login.php
    // =================================================================

    /**
     * @test
     * @group scene2
     *
     * VIDEO: Un individu tape https://monsite.fr/wp-login.php dans son navigateur.
     * L'ecran affiche une page 404 "Page introuvable".
     * Dans les logs, on voit : "Blocked direct wp-login.php access" avec l'IP.
     */
    public function scene2_attacker_tries_wp_login_gets_404(): void
    {
        $obfuscation = $this->createTestableObfuscation(
            requestUri: '/wp-login.php',
            isLoggedIn: false,
        );
        $obfuscation->setLoginSlug('acces-securise');

        // Le systeme detecte que c'est une URL par defaut
        $this->assertTrue(
            $obfuscation->isDefaultLoginRequest('/wp-login.php'),
            'Le systeme identifie /wp-login.php comme URL de login par defaut'
        );

        // Appel de blockDefaultLogin — doit trigger send404
        $obfuscation->blockDefaultLogin();

        // Verification : le log de securite a ete ecrit
        $this->assertCount(1, $this->logEntries, 'Un evenement de securite doit etre logue');
        $this->assertSame('warning', $this->logEntries[0]['level']);
        $this->assertSame('Blocked direct wp-login.php access', $this->logEntries[0]['message']);
        $this->assertSame('203.0.113.42', $this->logEntries[0]['context']['ip']);
        $this->assertSame('/wp-login.php', $this->logEntries[0]['context']['uri']);

        // Verification : la reponse 404 a ete envoyee
        $this->assertTrue($obfuscation->was404Sent(), 'Une reponse 404 doit etre envoyee');
    }

    /**
     * @test
     * @group scene2
     *
     * VIDEO: L'attaquant essaie aussi /wp-login.php?redirect_to=/wp-admin/
     * Meme resultat : 404.
     */
    public function scene2_attacker_tries_wp_login_with_query_params(): void
    {
        $obfuscation = $this->createTestableObfuscation(
            requestUri: '/wp-login.php?redirect_to=%2Fwp-admin%2F&reauth=1',
            isLoggedIn: false,
        );
        $obfuscation->setLoginSlug('acces-securise');

        $this->assertTrue(
            $obfuscation->isDefaultLoginRequest('/wp-login.php?redirect_to=%2Fwp-admin%2F&reauth=1'),
            'Les query params ne doivent pas contourner la detection'
        );

        $obfuscation->blockDefaultLogin();

        $this->assertTrue($obfuscation->was404Sent());
        $this->assertCount(1, $this->logEntries);
    }

    // =================================================================
    // SCENE 3 : Un visiteur malveillant tente /wp-admin/
    // =================================================================

    /**
     * @test
     * @group scene3
     *
     * VIDEO: L'attaquant essaie https://monsite.fr/wp-admin/
     * Le navigateur affiche "Page introuvable" — aucun indice WordPress.
     *
     * Note: /wp-admin/ ne contient pas "wp-login.php", mais WordPress
     * redirige automatiquement les non-connectes vers wp-login.php.
     * Le blocage s'effectue a ce moment-la.
     */
    public function scene3_attacker_tries_wp_admin_not_logged_in(): void
    {
        $obfuscation = $this->createTestableObfuscation(
            requestUri: '/wp-login.php?redirect_to=https%3A%2F%2Fmonsite.fr%2Fwp-admin%2F',
            isLoggedIn: false,
        );
        $obfuscation->setLoginSlug('acces-securise');

        $obfuscation->blockDefaultLogin();

        $this->assertTrue($obfuscation->was404Sent(), '/wp-admin/ redirect vers wp-login.php doit etre bloque');
        $this->assertCount(1, $this->logEntries);
        $this->assertStringContainsString('wp-admin', $this->logEntries[0]['context']['uri']);
    }

    // =================================================================
    // SCENE 4 : L'utilisateur legitime utilise /acces-securise
    // =================================================================

    /**
     * @test
     * @group scene4
     *
     * VIDEO: L'employe saisit https://monsite.fr/acces-securise
     * Le formulaire de login WordPress s'affiche normalement.
     */
    public function scene4_legitimate_user_accesses_custom_slug(): void
    {
        $obfuscation = $this->createTestableObfuscation(
            requestUri: '/acces-securise',
            isLoggedIn: false,
        );
        $obfuscation->setLoginSlug('acces-securise');

        // Le slug est reconnu
        $this->assertTrue(
            $obfuscation->uriMatchesSlug('/acces-securise', '/acces-securise'),
            'Le slug personnalise doit etre reconnu'
        );

        // Appel handleCustomLoginSlug — doit charger la page de login
        $obfuscation->handleCustomLoginSlug();

        $this->assertTrue(
            $obfuscation->wasLoginPageLoaded(),
            'La page de login doit etre chargee pour le slug personnalise'
        );

        // Aucun log de blocage
        $this->assertCount(0, $this->logEntries, 'Aucun evenement de blocage ne doit etre logue');
    }

    /**
     * @test
     * @group scene4
     *
     * VIDEO: Le slug fonctionne aussi avec un trailing slash /acces-securise/
     */
    public function scene4_custom_slug_works_with_trailing_slash(): void
    {
        $obfuscation = $this->createTestableObfuscation(
            requestUri: '/acces-securise/',
            isLoggedIn: false,
        );
        $obfuscation->setLoginSlug('acces-securise');

        $this->assertTrue(
            $obfuscation->uriMatchesSlug('/acces-securise/', '/acces-securise'),
            'Le trailing slash ne doit pas empecher la reconnaissance du slug'
        );

        $obfuscation->handleCustomLoginSlug();

        $this->assertTrue($obfuscation->wasLoginPageLoaded());
    }

    /**
     * @test
     * @group scene4
     *
     * VIDEO: Un slug different ne matche pas — securite stricte.
     */
    public function scene4_wrong_slug_does_not_match(): void
    {
        $obfuscation = $this->createTestableObfuscation(
            requestUri: '/wrong-slug',
            isLoggedIn: false,
        );
        $obfuscation->setLoginSlug('acces-securise');

        $this->assertFalse(
            $obfuscation->uriMatchesSlug('/wrong-slug', '/acces-securise'),
            'Un slug incorrect ne doit pas etre reconnu'
        );

        $obfuscation->handleCustomLoginSlug();

        $this->assertFalse($obfuscation->wasLoginPageLoaded());
    }

    // =================================================================
    // SCENE 5 : Reecriture automatique des URLs login/logout
    // =================================================================

    /**
     * @test
     * @group scene5
     *
     * VIDEO: Dans le code source de la page, on voit que toutes les URLs
     * de login pointent vers /acces-securise au lieu de /wp-login.php
     */
    public function scene5_login_url_is_rewritten(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('acces-securise');

        $rewritten = $obfuscation->filterLoginUrl(
            'https://monsite.fr/wp-login.php',
            'https://monsite.fr/wp-admin/'
        );

        $this->assertSame(
            'https://monsite.fr/acces-securise',
            $rewritten,
            'login_url doit pointer vers le slug personnalise'
        );
    }

    /**
     * @test
     * @group scene5
     */
    public function scene5_logout_url_is_rewritten(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('acces-securise');

        $rewritten = $obfuscation->filterLogoutUrl(
            'https://monsite.fr/wp-login.php?action=logout&_wpnonce=abc123',
            ''
        );

        $this->assertSame(
            'https://monsite.fr/acces-securise?action=logout&_wpnonce=abc123',
            $rewritten,
            'logout_url doit aussi pointer vers le slug personnalise'
        );
    }

    /**
     * @test
     * @group scene5
     */
    public function scene5_site_url_rewrites_wp_login_paths(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('acces-securise');

        $rewritten = $obfuscation->filterSiteUrl(
            'https://monsite.fr/wp-login.php?action=register',
            'wp-login.php?action=register',
            'login'
        );

        $this->assertSame(
            'https://monsite.fr/acces-securise?action=register',
            $rewritten,
            'site_url avec wp-login.php doit etre reecrit'
        );
    }

    /**
     * @test
     * @group scene5
     */
    public function scene5_site_url_does_not_rewrite_other_paths(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('acces-securise');

        $unchanged = $obfuscation->filterSiteUrl(
            'https://monsite.fr/wp-json/wp/v2/posts',
            'wp-json/wp/v2/posts',
            'rest'
        );

        $this->assertSame(
            'https://monsite.fr/wp-json/wp/v2/posts',
            $unchanged,
            'Les URLs non-login ne doivent pas etre reecrites'
        );
    }

    // =================================================================
    // SCENE 6 : Utilisateur connecte accede normalement a /wp-admin/
    // =================================================================

    /**
     * @test
     * @group scene6
     *
     * VIDEO: L'employe est deja connecte. Il clique sur "Tableau de bord"
     * et accede normalement a /wp-admin/ sans aucun blocage.
     */
    public function scene6_logged_in_user_can_access_wp_login(): void
    {
        $obfuscation = $this->createTestableObfuscation(
            requestUri: '/wp-login.php',
            isLoggedIn: true,
        );
        $obfuscation->setLoginSlug('acces-securise');

        $obfuscation->blockDefaultLogin();

        $this->assertFalse(
            $obfuscation->was404Sent(),
            'Un utilisateur connecte ne doit PAS recevoir une 404'
        );
        $this->assertCount(0, $this->logEntries, 'Aucun log de blocage pour un utilisateur connecte');
    }

    // =================================================================
    // SCENE 7 : Sans configuration, aucun hook n'est enregistre
    // =================================================================

    /**
     * @test
     * @group scene7
     *
     * VIDEO: Le slug n'est pas configure -> WordPress fonctionne normalement.
     * Aucun hook de securite n'est enregistre.
     */
    public function scene7_no_slug_means_no_hooks_registered(): void
    {
        $obfuscation = $this->createObfuscation();
        // Pas de setLoginSlug()

        $obfuscation->hooks();

        $this->assertCount(
            0,
            $this->registeredHooks,
            'Sans slug configure, aucun hook ne doit etre enregistre'
        );
    }

    /**
     * @test
     * @group scene7
     */
    public function scene7_empty_slug_is_ignored(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('');

        $obfuscation->hooks();

        $this->assertCount(0, $this->registeredHooks);
    }

    // =================================================================
    // SCENE BONUS : Tentatives de contournement
    // =================================================================

    /**
     * @test
     * @group bonus
     *
     * VIDEO: L'attaquant essaie des variantes pour contourner la protection.
     */
    public function bonus_path_traversal_does_not_match(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('acces-securise');

        $this->assertFalse($obfuscation->uriMatchesSlug('/acces-securise/../wp-admin', '/acces-securise'));
        $this->assertFalse($obfuscation->uriMatchesSlug('/acces-securise-extra', '/acces-securise'));
        $this->assertFalse($obfuscation->uriMatchesSlug('/ACCES-SECURISE', '/acces-securise'));
    }

    /**
     * @test
     * @group bonus
     *
     * VIDEO: Le log capture bien l'IP de l'attaquant pour chaque tentative bloquee.
     */
    public function bonus_attacker_ip_is_logged_on_every_blocked_attempt(): void
    {
        $attacker1Ip = '198.51.100.1';
        $attacker2Ip = '198.51.100.2';

        // Premier attaquant
        $ipResolver1 = $this->createMock(ClientIpResolverInterface::class);
        $ipResolver1->method('getClientIp')->willReturn($attacker1Ip);

        $obfuscation1 = new TestableAdminUrlObfuscation(
            $this->hookDispatcher, $this->logger, $this->requestContext, $ipResolver1,
            requestUri: '/wp-login.php', isLoggedIn: false,
        );
        $obfuscation1->setLoginSlug('acces-securise');
        $obfuscation1->blockDefaultLogin();

        // Deuxieme attaquant
        $ipResolver2 = $this->createMock(ClientIpResolverInterface::class);
        $ipResolver2->method('getClientIp')->willReturn($attacker2Ip);

        $obfuscation2 = new TestableAdminUrlObfuscation(
            $this->hookDispatcher, $this->logger, $this->requestContext, $ipResolver2,
            requestUri: '/wp-login.php', isLoggedIn: false,
        );
        $obfuscation2->setLoginSlug('acces-securise');
        $obfuscation2->blockDefaultLogin();

        $this->assertCount(2, $this->logEntries);
        $this->assertSame($attacker1Ip, $this->logEntries[0]['context']['ip']);
        $this->assertSame($attacker2Ip, $this->logEntries[1]['context']['ip']);
    }

    /**
     * @test
     * @group bonus
     *
     * VIDEO: Fluent API — le slug se configure en chaine.
     */
    public function bonus_fluent_configuration(): void
    {
        $obfuscation = $this->createObfuscation();

        $result = $obfuscation->setLoginSlug('mon-panel');

        $this->assertSame($obfuscation, $result, 'setLoginSlug doit retourner $this pour le chaining');
        $this->assertSame('mon-panel', $obfuscation->getLoginSlug());
    }

    /**
     * @test
     * @group bonus
     *
     * VIDEO: Le slug est nettoye des slashes superflus.
     */
    public function bonus_slug_is_trimmed_of_slashes(): void
    {
        $obfuscation = $this->createObfuscation();
        $obfuscation->setLoginSlug('/mon-panel/');

        $this->assertSame('mon-panel', $obfuscation->getLoginSlug());
    }

    // =================================================================
    // Factory helpers
    // =================================================================

    private function createObfuscation(): AdminUrlObfuscation
    {
        return new AdminUrlObfuscation(
            $this->hookDispatcher,
            $this->logger,
            $this->requestContext,
            $this->ipResolver,
        );
    }

    private function createTestableObfuscation(
        string $requestUri = '/',
        bool $isLoggedIn = false,
    ): TestableAdminUrlObfuscation {
        return new TestableAdminUrlObfuscation(
            $this->hookDispatcher,
            $this->logger,
            $this->requestContext,
            $this->ipResolver,
            requestUri: $requestUri,
            isLoggedIn: $isLoggedIn,
        );
    }
}

// =================================================================
// Testable subclass — overrides protected methods that call exit/WP
// =================================================================

/**
 * Test double that overrides side-effectful protected methods
 * (exit, require, status_header) to make them assertable.
 */
class TestableAdminUrlObfuscation extends AdminUrlObfuscation
{
    private bool $sent404 = false;
    private bool $loginPageLoaded = false;
    private string $fakeRequestUri;
    private bool $fakeIsLoggedIn;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
        RequestContextInterface $requestContext,
        ClientIpResolverInterface $ipResolver,
        string $requestUri = '/',
        bool $isLoggedIn = false,
    ) {
        parent::__construct($hookDispatcher, $logger, $requestContext, $ipResolver);
        $this->fakeRequestUri = $requestUri;
        $this->fakeIsLoggedIn = $isLoggedIn;
    }

    protected function getRequestUri(): string
    {
        return $this->fakeRequestUri;
    }

    protected function isLoggedIn(): bool
    {
        return $this->fakeIsLoggedIn;
    }

    protected function send404(): void
    {
        $this->sent404 = true;
        // No exit — testable
    }

    protected function loadLoginPage(): void
    {
        $this->loginPageLoaded = true;
        // No require/exit — testable
    }

    public function was404Sent(): bool
    {
        return $this->sent404;
    }

    public function wasLoginPageLoaded(): bool
    {
        return $this->loginPageLoaded;
    }
}
