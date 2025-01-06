<?php

namespace BackTo\Framework\PostMeta\Factory;

use Exception;
use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;
use BackTo\Framework\PostMeta\Entity\PostMeta;

class PostMetaFactory
{
    /**
     * Create a new post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     * @throws Exception
     */
    public function create(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        if (empty($key)) {
            throw new Exception('PostMeta key is required and should only contain lowercase letters and underscores.');
        }

        if (empty($label)) {
            $label = $this->generateLabel($key);
        }

        $args = $this->prepareDefaultArgs($args);

        $postMeta = new PostMeta();
        $postMeta
            ->setKey($key)
            ->setLabel($label)
            ->setPostTypes($postTypes)
            ->setArgs($args);

        if (isset($args['type'])) {
            $postMeta->setType($args['type']);
        }

        return $postMeta;
    }

    /**
     * Create a text post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createText(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        $args['type'] = 'text';
        return $this->createPostMeta($key, $label, $postTypes, $args);
    }

    /**
     * Create a textarea post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createTextarea(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        $args['type'] = 'textarea';
        return $this->createPostMeta($key, $label, $postTypes, $args);
    }

    /**
     * Create a checkbox post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createCheckbox(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        $args['type'] = 'checkbox';
        return $this->createPostMeta($key, $label, $postTypes, $args);
    }

    /**
     * Create a number post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createNumber(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        $args['type'] = 'number';
        return $this->createPostMeta($key, $label, $postTypes, $args);
    }

    /**
     * Create a date post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createDate(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        $args['type'] = 'date';
        return $this->createPostMeta($key, $label, $postTypes, $args);
    }

    /**
     * Create a datetime post meta
     *
     * @param string $key
     * @param string $label
     * @param array $postTypes
     * @param array $args
     * @return PostMetaInterface
     */
    public function createDatetime(string $key, string $label, array $postTypes, array $args = []): PostMetaInterface
    {
        $args['type'] = 'datetime-local';
        return $this->createPostMeta($key, $label, $postTypes, $args);
    }

    /**
     * Prepare default arguments
     *
     * @param array $args
     * @return array
     */
    private function prepareDefaultArgs(array $args): array
    {
        $args = $this->addArgIfNotExist($args, 'single', true);
        $args = $this->addArgIfNotExist($args, 'show_in_rest', true);
        $args = $this->addArgIfNotExist($args, 'show_in_admin_column', false);
        $args = $this->addArgIfNotExist($args, 'meta_box_position', 'normal');
        $args = $this->addArgIfNotExist($args, 'meta_box_priority', 'default');

        return $args;
    }

    /**
     * Add argument if it doesn't exist
     *
     * @param array $args
     * @param string $key
     * @param mixed $value
     * @return array
     */
    private function addArgIfNotExist(array $args, string $key, $value): array
    {
        if (!\array_key_exists($key, $args)) {
            $args[$key] = $value;
        }

        return $args;
    }

    /**
     * Generate a label from a key
     *
     * @param string $key
     * @return string
     */
    private function generateLabel(string $key): string
    {
        $label = str_replace(['_', '-'], ' ', $key);
        return ucwords($label);
    }
} 