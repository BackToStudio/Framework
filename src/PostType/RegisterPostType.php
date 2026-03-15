<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Exception\FrameworkException;
use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;

class RegisterPostType implements Hooks, ActivationHooks
{
    private PostTypeRegistry $registry;
    private PostTypeFactory $factory;
    private PostTypeRegistrarInterface $registrar;
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(
        PostTypeRegistry $postTypeRegistry,
        PostTypeFactory $postTypeFactory,
        PostTypeRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher
    ) {
        $this->registry = $postTypeRegistry;
        $this->factory = $postTypeFactory;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function activate(): void
    {
        $this->registerCustomPostTypes();
        $this->registrar->flushRewriteRules();
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
                return;
            }
            try {
                $newPostType = $this->factory->createPostType($postType->getKey(), $postType->getArgs());
                $this->registrar->register($newPostType->getKey(), $newPostType->getArgs());
            } catch (FrameworkException $exception) {
                \error_log($exception->getMessage());
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
            \error_log($e->getMessage());
        }

        return $this;
    }
}
