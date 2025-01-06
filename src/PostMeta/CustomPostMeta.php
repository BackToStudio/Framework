<?php

namespace BackTo\Framework\PostMeta;

use Exception;

class CustomPostMeta {
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
    private $type;

    /**
     * @var array
     */
    private $args;

    /**
     * @var array
     */
    private $postTypes;

    /**
     * CustomPostMeta constructor.
     *
     * @param string $key
     * @param string $label
     * @param string $type
     * @param array $postTypes
     * @param array $args
     *
     * @throws Exception
     */
    public function __construct(
        string $key,
        string $label,
        string $type = 'text',
        array $postTypes = [],
        array $args = []
    ) {
        if (empty($key)) {
            throw new Exception('PostMeta key is required and should only contain lowercase letters and underscores.');
        }

        $this->key = $key;
        $this->label = $label;
        $this->type = $type;
        $this->postTypes = $postTypes;
        $this->args = array_merge([
            'type' => $type,
            'description' => '',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => null,
            'auth_callback' => null,
            'default' => '',
            'required' => false,
        ], $args);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }
} 