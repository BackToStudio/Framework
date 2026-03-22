<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\HealthCheckStatus;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\Contracts\RemediationInterface;

/**
 * Executes auto-remediation actions when health checks report issues.
 *
 * After health checks run, this class matches degraded/unhealthy results
 * to registered remediation handlers and attempts automatic fixes.
 * All actions are logged and metricked for audit trail.
 */
final class AutoRemediation
{
    /** @var array<string, RemediationInterface> */
    private array $handlers = [];

    private readonly LoggerInterface $logger;
    private readonly AlertDispatcherInterface $alertDispatcher;
    private readonly MetricStoreInterface $metricStore;

    public function __construct(
        LoggerInterface $logger,
        AlertDispatcherInterface $alertDispatcher,
        MetricStoreInterface $metricStore,
    ) {
        $this->logger = $logger;
        $this->alertDispatcher = $alertDispatcher;
        $this->metricStore = $metricStore;
    }

    public function addHandler(RemediationInterface $handler): void
    {
        $this->handlers[$handler->getTargetCheck()] = $handler;
    }

    /**
     * Process health check results and attempt remediation for failures.
     *
     * @param array<string, HealthCheckResult> $results
     */
    public function process(array $results): void
    {
        foreach ($results as $name => $result) {
            if ($result->isHealthy()) {
                continue;
            }

            if (!isset($this->handlers[$name])) {
                continue;
            }

            $handler = $this->handlers[$name];
            $this->attemptRemediation($name, $result, $handler);
        }
    }

    /**
     * @return RemediationInterface[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    private function attemptRemediation(
        string $name,
        HealthCheckResult $result,
        RemediationInterface $handler,
    ): void {
        $this->logger->info(\sprintf(
            'Auto-remediation triggered for "%s" (%s): %s',
            $name,
            $result->getStatus(),
            $handler->getDescription(),
        ));

        try {
            $success = $handler->remediate($result->getMetadata());

            $this->metricStore->increment('remediation.attempts');

            if ($success) {
                $this->metricStore->increment('remediation.successes');
                $this->alertDispatcher->info(
                    \sprintf('Auto-remediation succeeded for "%s": %s', $name, $handler->getDescription()),
                    ['check' => $name, 'status' => $result->getStatus()],
                );
            } else {
                $this->metricStore->increment('remediation.failures');
                $this->alertDispatcher->warning(
                    \sprintf('Auto-remediation failed for "%s": %s', $name, $handler->getDescription()),
                    ['check' => $name, 'status' => $result->getStatus()],
                );
            }
        } catch (\Throwable $e) {
            $this->metricStore->increment('remediation.errors');
            $this->logger->error(\sprintf(
                'Auto-remediation error for "%s": %s',
                $name,
                $e->getMessage(),
            ));
        }
    }
}
