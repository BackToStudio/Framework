<?php

declare(strict_types=1);

namespace BackTo\Framework\Tests\Integration\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Security\Contracts\LoginThrottleInterface;
use BackTo\Framework\Security\LoginHardening;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: Login Brute Force Protection
 *
 * USE CASE VIDEO: "L'utilisateur tente de se connecter 10 fois
 * avec des identifiants errones. Le systeme le bloque progressivement."
 *
 * SCENARIO NARRATIF (storyboard video) :
 * ======================================
 *
 * SCENE 1 - Premiere tentative echouee
 *   L'attaquant saisit admin/wrongpass. Le message d'erreur est generique :
 *   "The login information you have entered is incorrect."
 *   -> Aucune info sur l'existence du compte n'est revelee.
 *
 * SCENE 2 - Tentatives 2 a 5 : accumulation des echecs
 *   Chaque echec est enregistre. Le compteur d'echecs monte.
 *   Les logs affichent l'IP et le nombre de tentatives.
 *   L'utilisateur n'est pas encore bloque.
 *
 * SCENE 3 - Tentatives 6 a 10 : seuil atteint, IP bloquee
 *   Au bout de N tentatives, l'IP est verrouillee.
 *   La prochaine tentative recoit un WP_Error "too_many_attempts"
 *   avec un delai d'attente en minutes.
 *
 * SCENE 4 - Tentative pendant le lockout
 *   L'attaquant essaie encore -> bloque immediatement.
 *   Le log indique "Login attempt blocked (IP throttled)"
 *   avec le temps restant.
 *
 * SCENE 5 - Blocage par compte (meme username, IPs differentes)
 *   Un attaquant distribue ses tentatives sur plusieurs IPs.
 *   Le systeme detecte l'accumulation par compte et bloque.
 *
 * SCENE 6 - Login reussi apres deblocage : compteurs remis a zero
 *   L'utilisateur legitime se connecte -> les compteurs IP et
 *   compte sont reinitialises.
 *
 * SCENE 7 - Les messages d'erreur sont toujours generiques
 *   Que l'utilisateur existe ou non, le message est identique.
 *   Impossible de deviner si le compte existe.
 */
class LoginBruteForceIntegrationTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private LoggerInterface $logger;
    private ClientIpResolverInterface $ipResolver;

    /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
    private array $logEntries = [];

    /** @var array<int, array{event: string, callback: callable}> */
    private array $registeredHooks = [];

    private InMemoryLoginThrottle $throttle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logEntries = [];
        $this->registeredHooks = [];

        // In-memory throttle with realistic thresholds
        $this->throttle = new InMemoryLoginThrottle(
            maxIpAttempts: 5,
            maxAccountAttempts: 8,
            lockoutSeconds: 900, // 15 minutes
        );

        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->hookDispatcher->method('addFilter')->willReturnCallback(
            function (string $event, callable $callback): void {
                $this->registeredHooks[] = ['event' => $event, 'callback' => $callback];
            }
        );
        $this->hookDispatcher->method('addAction')->willReturnCallback(
            function (string $event, callable $callback): void {
                $this->registeredHooks[] = ['event' => $event, 'callback' => $callback];
            }
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->method('warning')->willReturnCallback(
            function (string $message, array $context = []): void {
                $this->logEntries[] = ['level' => 'warning', 'message' => $message, 'context' => $context];
            }
        );
        $this->logger->method('info')->willReturnCallback(
            function (string $message, array $context = []): void {
                $this->logEntries[] = ['level' => 'info', 'message' => $message, 'context' => $context];
            }
        );

        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('203.0.113.42');
    }

    // =================================================================
    // SCENE 1 : Premiere tentative echouee — message generique
    // =================================================================

    /**
     * @test
     * @group scene1
     *
     * VIDEO: L'attaquant saisit "admin" / "wrongpass".
     * Le formulaire affiche : "The login information you have entered is incorrect."
     * Aucun indice sur l'existence du compte.
     */
    public function scene1_first_failed_attempt_shows_generic_error(): void
    {
        $hardening = $this->createHardening();

        // L'erreur est toujours generique
        $errorMessage = $hardening->genericLoginError();

        $this->assertSame(
            'The login information you have entered is incorrect.',
            $errorMessage,
            'Le message d\'erreur doit etre generique — aucun indice sur l\'existence du compte'
        );

        $this->assertStringNotContainsString('admin', $errorMessage);
        $this->assertStringNotContainsString('password', strtolower($errorMessage));
        $this->assertStringNotContainsString('username', strtolower($errorMessage));
    }

    /**
     * @test
     * @group scene1
     *
     * VIDEO: Apres l'echec, le log securite enregistre la tentative.
     */
    public function scene1_first_failure_is_recorded(): void
    {
        $hardening = $this->createHardening();

        $hardening->onLoginFailed('admin');

        $this->assertSame(1, $this->throttle->getFailedAttempts('203.0.113.42'));
        $this->assertCount(1, $this->logEntries);
        $this->assertSame('Failed login attempt', $this->logEntries[0]['message']);
        $this->assertSame('admin', $this->logEntries[0]['context']['username']);
        $this->assertSame('203.0.113.42', $this->logEntries[0]['context']['ip']);
        $this->assertSame(1, $this->logEntries[0]['context']['attempts']);
    }

    // =================================================================
    // SCENE 2 : Tentatives 2 a 5 — accumulation, pas encore bloque
    // =================================================================

    /**
     * @test
     * @group scene2
     *
     * VIDEO: L'attaquant reessaie 4 fois. Le compteur monte a 5.
     * A chaque tentative, le log montre le nombre croissant.
     * L'utilisateur n'est pas encore bloque.
     */
    public function scene2_accumulate_5_failures_not_yet_locked(): void
    {
        $hardening = $this->createHardening();

        for ($i = 1; $i <= 5; $i++) {
            $hardening->onLoginFailed('admin');
        }

        // 5 echecs enregistres
        $this->assertSame(5, $this->throttle->getFailedAttempts('203.0.113.42'));

        // 5 logs de tentatives echouees
        $this->assertCount(5, $this->logEntries);

        // Le compteur est visible dans chaque log
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($i + 1, $this->logEntries[$i]['context']['attempts']);
        }

        // Pas encore bloque : throttleLogin laisse passer
        $fakeUser = (object) ['ID' => 1];
        $result = $hardening->throttleLogin($fakeUser, 'admin', 'test');

        $this->assertSame(
            $fakeUser,
            $result,
            'Avec exactement 5 tentatives et un seuil de 5, le 6eme doit encore passer (le lock se fait sur la 6eme)'
        );
    }

    // =================================================================
    // SCENE 3 : 6eme tentative — seuil atteint, IP bloquee
    // =================================================================

    /**
     * @test
     * @group scene3
     *
     * VIDEO: L'attaquant fait sa 6eme tentative. L'IP est maintenant
     * bloquee. Le formulaire affiche "Too many failed login attempts.
     * Please try again in 15 minutes."
     */
    public function scene3_sixth_attempt_triggers_ip_lockout(): void
    {
        $hardening = $this->createHardening();

        // Simuler 6 echecs (seuil = 5, le 6eme declenche le lock)
        for ($i = 1; $i <= 6; $i++) {
            $hardening->onLoginFailed('admin');
        }

        // L'IP est maintenant bloquee
        $this->assertTrue(
            $this->throttle->isLocked('203.0.113.42'),
            'L\'IP doit etre verrouillee apres 6 tentatives (seuil = 5)'
        );

        // La prochaine tentative d'auth est bloquee
        $fakeUser = (object) ['ID' => 1];
        $result = $hardening->throttleLogin($fakeUser, 'admin', 'wrongpass');

        $this->assertIsObject($result);
        $this->assertSame('too_many_attempts', $result->code,
            'throttleLogin doit retourner un WP_Error quand l\'IP est bloquee'
        );

        $this->assertStringContainsString(
            '15 minutes',
            $result->message,
            'Le message doit indiquer le delai d\'attente'
        );
    }

    // =================================================================
    // SCENE 4 : Tentative pendant le lockout — bloque immediatement
    // =================================================================

    /**
     * @test
     * @group scene4
     *
     * VIDEO: L'attaquant insiste pendant le lockout. Chaque tentative
     * est bloquee instantanement avec un log "IP throttled".
     */
    public function scene4_attempts_during_lockout_are_instantly_blocked(): void
    {
        $hardening = $this->createHardening();

        // Declencher le lockout
        for ($i = 0; $i < 6; $i++) {
            $hardening->onLoginFailed('admin');
        }
        $this->logEntries = []; // Reset logs pour les prochaines assertions

        // Tentative pendant le lockout
        $fakeUser = (object) ['ID' => 1];
        $result = $hardening->throttleLogin($fakeUser, 'admin', 'another-try');

        $this->assertIsObject($result);
        $this->assertSame('too_many_attempts', $result->code);

        // Le log indique le blocage IP
        $this->assertCount(1, $this->logEntries);
        $this->assertSame('Login attempt blocked (IP throttled)', $this->logEntries[0]['message']);
        $this->assertSame('203.0.113.42', $this->logEntries[0]['context']['ip']);
        $this->assertArrayHasKey('remaining_seconds', $this->logEntries[0]['context']);
        $this->assertGreaterThan(0, $this->logEntries[0]['context']['remaining_seconds']);
    }

    /**
     * @test
     * @group scene4
     *
     * VIDEO: 3 tentatives supplementaires pendant le lockout.
     * Toutes bloquees, 3 logs de blocage generes.
     */
    public function scene4_multiple_attempts_during_lockout_all_blocked(): void
    {
        $hardening = $this->createHardening();

        // Declencher le lockout
        for ($i = 0; $i < 6; $i++) {
            $hardening->onLoginFailed('admin');
        }
        $this->logEntries = [];

        // 3 tentatives supplementaires
        $fakeUser = (object) ['ID' => 1];
        for ($i = 0; $i < 3; $i++) {
            $result = $hardening->throttleLogin($fakeUser, 'admin', 'try-' . $i);
            $this->assertIsObject($result);
        $this->assertSame('too_many_attempts', $result->code);
        }

        // 3 logs de blocage
        $this->assertCount(3, $this->logEntries);
        foreach ($this->logEntries as $log) {
            $this->assertSame('Login attempt blocked (IP throttled)', $log['message']);
        }
    }

    // =================================================================
    // SCENE 5 : Attaque distribuee — blocage par compte
    // =================================================================

    /**
     * @test
     * @group scene5
     *
     * VIDEO: L'attaquant utilise un botnet avec des IPs differentes
     * pour attaquer le meme compte "admin". Le systeme detecte
     * l'accumulation par nom d'utilisateur et bloque le compte.
     */
    public function scene5_distributed_attack_triggers_account_lockout(): void
    {
        // Creer 3 IPs differentes, chacune fait des tentatives sur "admin"
        $ips = ['198.51.100.1', '198.51.100.2', '198.51.100.3'];

        foreach ($ips as $ip) {
            $hardening = $this->createHardeningWithIp($ip);

            // 3 tentatives par IP = 9 total sur le compte "admin"
            for ($i = 0; $i < 3; $i++) {
                $hardening->onLoginFailed('admin');
            }
        }

        // Aucune IP individuelle n'est bloquee (3 < seuil IP de 5)
        foreach ($ips as $ip) {
            $this->assertFalse(
                $this->throttle->isLocked($ip),
                "L'IP $ip ne doit pas etre bloquee individuellement (seulement 3 tentatives)"
            );
        }

        // Mais le compte "admin" est bloque (9 > seuil compte de 8)
        $this->assertTrue(
            $this->throttle->isAccountLocked('admin'),
            'Le compte "admin" doit etre bloque apres 9 tentatives distribuees (seuil = 8)'
        );

        // La prochaine tentative est bloquee meme depuis une nouvelle IP
        $hardening = $this->createHardeningWithIp('192.0.2.99');

        $fakeUser = (object) ['ID' => 1];
        $result = $hardening->throttleLogin($fakeUser, 'admin', 'another-attempt');

        $this->assertIsObject($result);
        $this->assertSame('too_many_attempts', $result->code);
    }

    /**
     * @test
     * @group scene5
     *
     * VIDEO: Le log montre que le blocage est par "account throttled".
     */
    public function scene5_account_lockout_log_message(): void
    {
        // Accumuler 9 tentatives distribuees
        for ($i = 0; $i < 9; $i++) {
            $hardening = $this->createHardeningWithIp('198.51.100.' . $i);
            $hardening->onLoginFailed('admin');
        }
        $this->logEntries = [];

        // Tentative sur le compte bloque
        $hardening = $this->createHardeningWithIp('192.0.2.50');

        $fakeUser = (object) ['ID' => 1];
        $hardening->throttleLogin($fakeUser, 'admin', 'test');

        $this->assertCount(1, $this->logEntries);
        $this->assertSame(
            'Login attempt blocked (account throttled)',
            $this->logEntries[0]['message']
        );
    }

    // =================================================================
    // SCENE 6 : Login reussi — compteurs remis a zero
    // =================================================================

    /**
     * @test
     * @group scene6
     *
     * VIDEO: Apres le deblocage, l'utilisateur legitime se connecte
     * avec succès. Les compteurs IP et compte sont reinitialises.
     * Le log affiche "Successful login".
     */
    public function scene6_successful_login_resets_all_counters(): void
    {
        $hardening = $this->createHardening();

        // Accumuler 3 echecs (pas encore bloque)
        for ($i = 0; $i < 3; $i++) {
            $hardening->onLoginFailed('admin');
        }

        $this->assertSame(3, $this->throttle->getFailedAttempts('203.0.113.42'));
        $this->logEntries = [];

        // Login reussi
        $hardening->onLoginSuccess('admin');

        // Compteurs remis a zero
        $this->assertSame(
            0,
            $this->throttle->getFailedAttempts('203.0.113.42'),
            'Le compteur IP doit etre reinitialise apres un login reussi'
        );

        $this->assertFalse(
            $this->throttle->isAccountLocked('admin'),
            'Le compte ne doit plus etre bloque apres un login reussi'
        );

        // Log de succes
        $this->assertCount(1, $this->logEntries);
        $this->assertSame('info', $this->logEntries[0]['level']);
        $this->assertSame('Successful login', $this->logEntries[0]['message']);
        $this->assertSame('admin', $this->logEntries[0]['context']['username']);
    }

    // =================================================================
    // SCENE 7 : Messages toujours generiques
    // =================================================================

    /**
     * @test
     * @group scene7
     *
     * VIDEO: Que l'on tape un username existant ou non, un bon ou
     * mauvais mot de passe : le message est TOUJOURS identique.
     * Impossible d'enumerer les comptes.
     */
    public function scene7_error_message_is_always_identical(): void
    {
        $hardening = $this->createHardening();

        // Meme message pour n'importe quel scenario
        $msg1 = $hardening->genericLoginError();
        $msg2 = $hardening->genericLoginError();
        $msg3 = $hardening->genericLoginError();

        $this->assertSame($msg1, $msg2);
        $this->assertSame($msg2, $msg3);
        $this->assertSame(
            'The login information you have entered is incorrect.',
            $msg1
        );
    }

    /**
     * @test
     * @group scene7
     *
     * VIDEO: Les hooks sont bien enregistres sur le bon filtre.
     */
    public function scene7_hooks_are_registered_correctly(): void
    {
        $hardening = $this->createHardening();
        $hardening->hooks();

        $hookEvents = array_column($this->registeredHooks, 'event');

        $this->assertContains('authenticate', $hookEvents, 'Doit intercepter authenticate pour le throttle');
        $this->assertContains('login_errors', $hookEvents, 'Doit filtrer login_errors pour le message generique');
        $this->assertContains('wp_login_failed', $hookEvents, 'Doit ecouter wp_login_failed');
        $this->assertContains('wp_login', $hookEvents, 'Doit ecouter wp_login pour reset');
    }

    // =================================================================
    // SCENE BONUS : Tentatives avec username/password vides
    // =================================================================

    /**
     * @test
     * @group bonus
     *
     * VIDEO: Les soumissions vides sont ignorees — pas de throttle.
     */
    public function bonus_empty_credentials_are_not_throttled(): void
    {
        $hardening = $this->createHardening();

        $fakeUser = (object) ['ID' => 1];

        // Username vide
        $result = $hardening->throttleLogin($fakeUser, '', 'password');
        $this->assertSame($fakeUser, $result, 'Username vide ne doit pas declencher le throttle');

        // Password vide
        $result = $hardening->throttleLogin($fakeUser, 'admin', '');
        $this->assertSame($fakeUser, $result, 'Password vide ne doit pas declencher le throttle');
    }

    /**
     * @test
     * @group bonus
     *
     * VIDEO: Scenario complet — 10 tentatives en sequence rapide.
     * Comportement exact observe a l'ecran.
     */
    public function bonus_full_scenario_10_rapid_attempts(): void
    {
        $hardening = $this->createHardening();
        $fakeUser = (object) ['ID' => 1];

        $results = [];

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            // Enregistrer l'echec
            $hardening->onLoginFailed('admin');

            // Tenter le login
            $result = $hardening->throttleLogin($fakeUser, 'admin', 'wrong-' . $attempt);
            $results[$attempt] = $result;
        }

        // Tentatives 1-5 : passent (l'IP n'est pas encore bloquee)
        for ($i = 1; $i <= 5; $i++) {
            $this->assertSame(
                $fakeUser,
                $results[$i],
                "Tentative $i doit passer (pas encore bloque)"
            );
        }

        // Tentatives 6-10 : bloquees par IP throttle
        for ($i = 6; $i <= 10; $i++) {
            $this->assertIsObject($results[$i], "Tentative $i doit etre bloquee");
            $this->assertSame(
                'too_many_attempts',
                $results[$i]->code,
                "Tentative $i doit etre bloquee (IP verrouillee)"
            );
        }

        // Verification des logs
        $failedLogs = array_filter($this->logEntries, fn ($l) => $l['message'] === 'Failed login attempt');
        $blockedLogs = array_filter($this->logEntries, fn ($l) => str_contains($l['message'], 'blocked'));

        $this->assertCount(10, $failedLogs, '10 tentatives echouees doivent etre loguees');
        $this->assertCount(5, $blockedLogs, '5 tentatives bloquees (6e a 10e) doivent etre loguees');
    }

    // =================================================================
    // Factory helper
    // =================================================================

    private function createHardening(): TestableLoginHardening
    {
        return new TestableLoginHardening(
            $this->hookDispatcher,
            $this->throttle,
            $this->logger,
            $this->ipResolver,
        );
    }

    private function createHardeningWithIp(string $ip): TestableLoginHardening
    {
        /** @var ClientIpResolverInterface&MockObject $ipResolver */
        $ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $ipResolver->method('getClientIp')->willReturn($ip);

        return new TestableLoginHardening(
            $this->hookDispatcher,
            $this->throttle,
            $this->logger,
            $ipResolver,
        );
    }
}

// =================================================================
// In-memory LoginThrottle — realistic throttle for integration tests
// =================================================================

/**
 * In-memory implementation of LoginThrottleInterface for integration testing.
 *
 * Tracks attempts per IP and per account with configurable thresholds.
 */
/**
 * Testable subclass that avoids WP_Error dependency (not available outside WordPress).
 * Returns a stdClass with code/remainingSeconds instead.
 */
class TestableLoginHardening extends LoginHardening
{
    protected function createLockoutError(int $remainingSeconds): mixed
    {
        $error = new \stdClass();
        $error->code = 'too_many_attempts';
        $error->remainingSeconds = $remainingSeconds;
        $error->message = sprintf(
            'Too many failed login attempts. Please try again in %d minutes.',
            (int) ceil($remainingSeconds / 60)
        );

        return $error;
    }
}

class InMemoryLoginThrottle implements LoginThrottleInterface
{
    /** @var array<string, int> */
    private array $ipAttempts = [];

    /** @var array<string, int> */
    private array $accountAttempts = [];

    public function __construct(
        private readonly int $maxIpAttempts = 5,
        private readonly int $maxAccountAttempts = 8,
        private readonly int $lockoutSeconds = 900,
    ) {
    }

    public function recordFailedAttempt(string $ip): void
    {
        $this->ipAttempts[$ip] = ($this->ipAttempts[$ip] ?? 0) + 1;
    }

    public function getFailedAttempts(string $ip): int
    {
        return $this->ipAttempts[$ip] ?? 0;
    }

    public function isLocked(string $ip): bool
    {
        return ($this->ipAttempts[$ip] ?? 0) > $this->maxIpAttempts;
    }

    public function reset(string $ip): void
    {
        unset($this->ipAttempts[$ip]);
    }

    public function getLockoutRemainingSeconds(string $ip): int
    {
        return $this->lockoutSeconds;
    }

    public function recordFailedAccountAttempt(string $username): void
    {
        $this->accountAttempts[$username] = ($this->accountAttempts[$username] ?? 0) + 1;
    }

    public function isAccountLocked(string $username): bool
    {
        return ($this->accountAttempts[$username] ?? 0) > $this->maxAccountAttempts;
    }

    public function getAccountLockoutRemainingSeconds(string $username): int
    {
        return $this->lockoutSeconds;
    }

    public function resetAccount(string $username): void
    {
        unset($this->accountAttempts[$username]);
    }
}
