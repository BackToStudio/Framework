<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Exception\FrameworkException;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;

final class RegisterPostType implements Hooks
{
    private readonly PostTypeRegistry $registry;
    private readonly PostTypeFactory $factory;
    private readonly PostTypeRegistrarInterface $registrar;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly LoggerInterface $logger;

    public function __construct(
        PostTypeRegistry $postTypeRegistry,
        PostTypeFactory $postTypeFactory,
        PostTypeRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
    ) {
        $this->registry = $postTypeRegistry;
        $this->factory = $postTypeFactory;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'registerCustomPostTypes']);
        $this->hookDispatcher->addAction('registered_post_type', [$this->registrar, 'flushRewriteRules']);
        $this->hookDispatcher->addAction('unregistered_post_type', [$this->registrar, 'flushRewriteRules']);
    }

    public function registerCustomPostTypes(): void
    {
        foreach ($this->registry->getPostTypes() as $postType) {
            if ($this->registrar->exists($postType->getKey())) {
                continue;
            }
            try {
                $newPostType = $this->factory->createPostType($postType->getKey(), $postType->getArgs());
                $this->registrar->register($newPostType->getKey(), $newPostType->getArgs());
            } catch (FrameworkException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    public function add(string $name, array $args = []): self
    {
        try {
            $newPostType = $this->factory->createPostType($name, $args);
            $this->registry->add($newPostType);
        } catch (FrameworkException $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
