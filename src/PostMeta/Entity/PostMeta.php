<?php

namespace BackTo\Framework\PostMeta\Entity;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use Exception;

class PostMeta implements PostMetaInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $type = 'text';

    /**
     * @var array
     */
    private $args = [
        'description' => '',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => null,
        'auth_callback' => null,
        'default' => '',
        'required' => false,
        'show_in_admin_column' => false,
        'meta_box_position' => 'normal',
        'meta_box_priority' => 'default',
        'custom_render' => null,
    ];

    /**
     * @var array
     */
    private $postTypes = [];

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return PostMetaInterface
     * @throws Exception
     */
    public function setKey(string $key): PostMetaInterface
    {
        if (empty($key)) {
            throw new Exception('PostMeta key is required and should only contain lowercase letters and underscores.');
        }

        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return PostMetaInterface
     */
    public function setLabel(string $label): PostMetaInterface
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return PostMetaInterface
     */
    public function setType(string $type): PostMetaInterface
    {
        $this->type = $type;
        $this->args['type'] = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     * @return PostMetaInterface
     */
    public function setArgs(array $args): PostMetaInterface
    {
        $this->args = array_merge($this->args, $args);
        return $this;
    }

    /**
     * @return array
     */
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }

    /**
     * @param array $postTypes
     * @return PostMetaInterface
     */
    public function setPostTypes(array $postTypes): PostMetaInterface
    {
        $this->postTypes = $postTypes;
        return $this;
    }

    /**
     * @param string $postType
     * @return PostMetaInterface
     */
    public function addPostType(string $postType): PostMetaInterface
    {
        if (!in_array($postType, $this->postTypes)) {
            $this->postTypes[] = $postType;
        }
        return $this;
    }

    /**
     * Hook method called before the meta value is saved
     * Can be overridden by child classes
     * 
     * @param int $post_id
     * @param mixed $value
     * @param mixed $old_value
     * @return void
     */
    public function beforeSave(int $post_id, $value, $old_value): void
    {
        // Override in child class if needed
    }

    /**
     * Hook method called after the meta value is saved
     * Can be overridden by child classes
     * 
     * @param int $post_id
     * @param mixed $value
     * @param mixed $old_value
     * @return void
     */
    public function afterSave(int $post_id, $value, $old_value): void
    {
        // Override in child class if needed
    }

    /**
     * Custom render method for the meta box
     * Can be overridden by child classes
     * 
     * @param \WP_Post $post
     * @param array $metabox
     * @return void
     */
    public function renderCustomMetaBox($post, $metabox): void
    {
        // Override in child class if needed
    }
} 