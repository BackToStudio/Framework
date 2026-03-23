<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Auth;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

final class SessionManager implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly RequestContextInterface $requestContext;
    private readonly int $maxSessions;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        RequestContextInterface $requestContext,
        int $maxSessions = 1,
    ) {
        if ($maxSessions < 1) {
            throw new \InvalidArgumentException(\sprintf('Max sessions must be at least 1, got %d.', $maxSessions));
        }

        $this->hookDispatcher = $hookDispatcher;
        $this->requestContext = $requestContext;
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
        $sessionInfo['ip'] = $this->requestContext->getRemoteAddr();
        $sessionInfo['ua'] = $this->requestContext->getUserAgent();
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

        $userId = (int) $user->ID;

        if ($userId <= 0) {
            return;
        }

        $this->destroyExcessSessions($userId);
    }

    /**
     * Limit the number of concurrent sessions per user.
     */
    /**
     * Limit the number of concurrent sessions per user.
     *
     * Re-fetches sessions after destruction to mitigate race conditions
     * where new sessions are created between get_all() and destroy().
     * Limited to 2 passes to avoid infinite loops.
     */
    protected function destroyExcessSessions(int $userId): void
    {
        $manager = $this->getWpSessionTokens($userId);

        if ($manager === null) {
            return;
        }

        // Two passes maximum to handle concurrent session creation.
        for ($pass = 0; $pass < 2; $pass++) {
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
    }

    
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
