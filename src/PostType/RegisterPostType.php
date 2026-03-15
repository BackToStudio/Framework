<?php

namespace BackTo\Framework\PostType;

use Exception;
use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;

class RegisterPostType implements Hooks, ActivationHooks
{

    /**
     * @var PostTypeRegistry
     */
    private $registry;

    /**
     * @var PostTypeFactory
     */
    private $factory;

    /**
     * @var PostTypeRegistrarInterface
     */
    private $registrar;

    /**
     * @var HookDispatcherInterface
     */
    private $hookDispatcher;

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

    public function activate()
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
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
    }

    /**
     * Register new Custom Post Type on the fly.
     *
     * @param string $name
     * @param array $args
     *
     * @return $this
     */
    public function add(string $name, array $args = []): RegisterPostType
    {
        try {
            $newPostType = $this->factory->createPostType($name, $args);
            $this->registry->add($newPostType);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $this;
    }
}
