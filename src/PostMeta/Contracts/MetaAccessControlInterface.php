<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

/**
 * Access control and visibility contract for a post meta field.
 *
 * Segregated from PostMetaStructureInterface (ISP):
 * consumers that deal with REST API visibility, sanitization,
 * and authorization depend on this interface.
 */
interface MetaAccessControlInterface
{
    public function getSanitizeCallback(): ?callable;

    public function setSanitizeCallback(callable $callback): MetaAccessControlInterface;

    public function getAuthCallback(): ?callable;

    public function setAuthCallback(?callable $callback): MetaAccessControlInterface;

    public function isShowInRest(): bool;

    public function dontShowInRest(): MetaAccessControlInterface;

    public function showInRest(): MetaAccessControlInterface;

    public function setShowInRest(bool $showInRest): MetaAccessControlInterface;

    public function isRevisionsEnabled(): bool;

    public function setRevisionsEnabled(bool $enabled): MetaAccessControlInterface;
}
