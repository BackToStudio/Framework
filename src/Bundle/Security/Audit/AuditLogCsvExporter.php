<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Audit;

use BackTo\Framework\Cache\Contracts\TransientStoreInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;

/**
 * Exports audit log events to CSV format.
 */
class AuditLogCsvExporter
{
    private const EXPORT_LIMIT = 10000;
    private const EXPORT_COOLDOWN_SECONDS = 60;

    private readonly AuditLogRepositoryInterface $repository;
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly ?TransientStoreInterface $transientStore;

    public function __construct(
        AuditLogRepositoryInterface $repository,
        ResponseEmitterInterface $responseEmitter,
        ?TransientStoreInterface $transientStore = null,
    ) {
        $this->repository = $repository;
        $this->responseEmitter = $responseEmitter;
        $this->transientStore = $transientStore;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function export(array $filters): void
    {
        $events = $this->repository->getEvents($filters, self::EXPORT_LIMIT, 0);

        $this->sendCsvHeaders();

        $output = $this->openOutputStream();

        if ($output === false) {
            return;
        }

        fputcsv($output, ['Timestamp', 'Event', 'Severity', 'Context']);

        foreach ($events as $event) {
            $timestamp = (int) ($event['timestamp'] ?? 0);
            $context = $event['context'] ?? [];
            $contextStr = is_array($context) ? (string) json_encode($context) : '';

            fputcsv($output, [
                gmdate('Y-m-d H:i:s', $timestamp),
                (string) ($event['event'] ?? ''),
                (string) ($event['severity'] ?? ''),
                $contextStr,
            ]);
        }

        fclose($output);
    }

    public function canExport(): bool
    {
        if ($this->transientStore === null) {
            return true;
        }

        return $this->transientStore->get('backto_audit_export_lock') === false;
    }

    public function markExported(): void
    {
        $this->transientStore?->set('backto_audit_export_lock', '1', self::EXPORT_COOLDOWN_SECONDS);
    }

    protected function sendCsvHeaders(): void
    {
        $this->responseEmitter->sendHeader('Content-Type: text/csv; charset=utf-8');
        $this->responseEmitter->sendHeader('Content-Disposition: attachment; filename="audit-log-' . gmdate('Y-m-d') . '.csv"');
    }

    /**
     * @return resource|false
     */
    protected function openOutputStream()
    {
        return fopen('php://output', 'w');
    }
}
