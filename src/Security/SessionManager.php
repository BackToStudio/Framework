<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class SessionManager implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;
    private int $maxSessions;

    public function __construct(HookDispatcherInterface $hookDispatcher, int $maxSessions = 1)
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->maxSessions = $maxSessions;
    }

    public function getName(): string
    {
        return 'session_manager';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('attach_session_information', [$this, 'attachSessionInfo']);
        $this->hookDispatcher->addAction('wp_login', [$this, 'enforceConcurrentSessionLimit'], 10, 2);
        $this->hookDispatcher->addFilter('session_token_manager', [$this, 'getSessionTokenManager']);
    }

    /**
     * Attach additional metadata to session tokens.
     *
     * @param array<string, mixed> $sessionInfo
     * @return array<string, mixed>
     */
    public function attachSessionInfo(array $sessionInfo): array
    {
        $sessionInfo['ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sessionInfo['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionInfo['created'] = time();

        return $sessionInfo;
    }

    /**
     * Destroy oldest sessions when exceeding the maximum.
     */
    public function enforceConcurrentSessionLimit(string $userLogin, mixed $user): void
    {
        if (!is_object($user) || !isset($user->ID)) {
            return;
        }

        $this->destroyExcessSessions((int) $user->ID);
    }

    /**
     * Limit the number of concurrent sessions per user.
     */
    protected function destroyExcessSessions(int $userId): void
    {
        $manager = $this->getWpSessionTokens($userId);

        if ($manager === null) {
            return;
        }

        $sessions = $manager->get_all();

        if (count($sessions) <= $this->maxSessions) {
            return;
        }

        // Sort sessions by login time (oldest first)
        uasort($sessions, static function (array $a, array $b): int {
            return ($a['login'] ?? 0) <=> ($b['login'] ?? 0);
        });

        $tokensToDestroy = count($sessions) - $this->maxSessions;
        $destroyed = 0;

        foreach (array_keys($sessions) as $token) {
            if ($destroyed >= $tokensToDestroy) {
                break;
            }
            $manager->destroy($token);
            $destroyed++;
        }
    }

    /**
     * @return \WP_Session_Tokens|null
     */
    protected function getWpSessionTokens(int $userId): mixed
    {
        if (!class_exists(\WP_Session_Tokens::class)) {
            return null;
        }

        return \WP_Session_Tokens::get_instance($userId);
    }

    public function getMaxSessions(): int
    {
        return $this->maxSessions;
    }

    /**
     * @return class-string
     */
    public function getSessionTokenManager(): string
    {
        return \WP_User_Meta_Session_Tokens::class;
    }
}
