<?php

namespace Fantassin\Core\WordPress\PostType;

use Exception;
use Fantassin\Core\WordPress\Contracts\ActivationHooks;
use Fantassin\Core\WordPress\Contracts\Hooks;

class RegisterPostType implements Hooks, ActivationHooks
{

    /**
     * @var PostTypeRepository
     */
    private $registry;

    /**
     * @var PostTypeFactory
     */
    private $factory;

    public function __construct(PostTypeRegistry $postTypeRegistry, PostTypeFactory $postTypeFactory)
    {
        $this->registry = $postTypeRegistry;
        $this->factory = $postTypeFactory;
    }

    public function activate()
    {
        $this->registerCustomPostTypes();
        \flush_rewrite_rules();
    }

    public function hooks()
    {
        \add_action('init', [$this, 'registerCustomPostTypes']);
        \add_action('registered_post_type', 'flush_rewrite_rules');
        \add_action('unregistered_post_type', 'flush_rewrite_rules');
    }

    public function registerCustomPostTypes()
    {
        foreach ($this->registry->getPostTypes() as $postType) {
            if (\post_type_exists($postType->getKey())) {
                return;
            }
            try {
                $newPostType = $this->factory->createPostType($postType->getKey(), $postType->getArgs());
                \register_post_type($newPostType->getKey(), $newPostType->getArgs());
            } catch (Exception $exception) {
                \write_log($exception->getMessage());
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
