<?php

namespace BackTo\Framework\PostMeta;

use BackTo\Framework\PostMeta\Entity\PostMeta;

class RegisterPostMeta {
    /**
     * @var array
     */
    private $postMetas = [];

    /**
     * Add a new post meta
     *
     * @param string $key
     * @param string $label
     * @param string $type
     * @param array|string $postTypes
     * @param array $args
     * @return $this
     */
    public function add(
        string $key,
        string $label,
        string $type = 'text',
        $postTypes = [],
        array $args = []
    ): self {
        if (is_string($postTypes)) {
            $postTypes = [$postTypes];
        }

        $this->postMetas[] = new PostMeta($key, $label, $type, $postTypes, $args);

        return $this;
    }

    /**
     * Register all post metas
     */
    public function register(): void
    {
        foreach ($this->postMetas as $postMeta) {
            foreach ($postMeta->getPostTypes() as $postType) {
                register_post_meta(
                    $postType,
                    $postMeta->getKey(),
                    $postMeta->getArgs()
                );

                if ($postMeta->getArgs()['custom_render']) {
                    add_action('add_meta_boxes', function() use ($postMeta, $postType) {
                        add_meta_box(
                            $postMeta->getKey(),
                            $postMeta->getLabel(),
                            $postMeta->getArgs()['custom_render'],
                            $postType,
                            $postMeta->getArgs()['meta_box_position'],
                            $postMeta->getArgs()['meta_box_priority'],
                            ['post_meta' => $postMeta]
                        );
                    });
                } else {
                    add_action('add_meta_boxes', function() use ($postMeta, $postType) {
                        add_meta_box(
                            $postMeta->getKey(),
                            $postMeta->getLabel(),
                            [$this, 'renderMetaBox'],
                            $postType,
                            $postMeta->getArgs()['meta_box_position'],
                            $postMeta->getArgs()['meta_box_priority'],
                            ['post_meta' => $postMeta]
                        );
                    });
                }

                if ($postMeta->getArgs()['show_in_admin_column']) {
                    add_filter("manage_{$postType}_posts_columns", function($columns) use ($postMeta) {
                        $columns[$postMeta->getKey()] = $postMeta->getLabel();
                        return $columns;
                    });

                    add_action("manage_{$postType}_posts_custom_column", function($column, $post_id) use ($postMeta) {
                        if ($column === $postMeta->getKey()) {
                            $value = get_post_meta($post_id, $postMeta->getKey(), true);
                            echo esc_html($value);
                        }
                    }, 10, 2);
                }
            }
        }

        add_action('save_post', [$this, 'save']);
    }

    /**
     * Render meta box content
     *
     * @param \WP_Post $post
     * @param array $metabox
     */
    public function renderMetaBox($post, $metabox): void
    {
        $postMeta = $metabox['args']['post_meta'];
        $value = get_post_meta($post->ID, $postMeta->getKey(), true);
        $args = $postMeta->getArgs();
        
        wp_nonce_field($postMeta->getKey() . '_nonce', $postMeta->getKey() . '_nonce');
        
        switch ($postMeta->getType()) {
            case 'textarea':
                echo sprintf(
                    '<textarea name="%s" id="%s" class="widefat"%s>%s</textarea>',
                    esc_attr($postMeta->getKey()),
                    esc_attr($postMeta->getKey()),
                    $args['required'] ? ' required' : '',
                    esc_textarea($value)
                );
                break;
                
            case 'checkbox':
                echo sprintf(
                    '<input type="checkbox" name="%s" id="%s" value="1"%s%s>',
                    esc_attr($postMeta->getKey()),
                    esc_attr($postMeta->getKey()),
                    checked($value, '1', false),
                    $args['required'] ? ' required' : ''
                );
                break;

            case 'time':
                echo sprintf(
                    '<input type="time" name="%s" id="%s" value="%s" class="widefat"%s%s>',
                    esc_attr($postMeta->getKey()),
                    esc_attr($postMeta->getKey()),
                    esc_attr($value),
                    isset($args['step']) ? ' step="' . esc_attr($args['step']) . '"' : '',
                    $args['required'] ? ' required' : ''
                );
                break;
                
            default:
                echo sprintf(
                    '<input type="%s" name="%s" id="%s" value="%s" class="widefat"%s>',
                    esc_attr($postMeta->getType()),
                    esc_attr($postMeta->getKey()),
                    esc_attr($postMeta->getKey()),
                    esc_attr($value),
                    $args['required'] ? ' required' : ''
                );
        }
        
        if (!empty($args['description'])) {
            echo sprintf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Save post meta value
     *
     * @param int $postId
     */
    public function save(int $postId): void
    {
        foreach ($this->postMetas as $postMeta) {
            $nonceKey = $postMeta->getKey() . '_nonce';
            
            if (!isset($_POST[$nonceKey]) || !wp_verify_nonce($_POST[$nonceKey], $nonceKey)) {
                continue;
            }
            
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                continue;
            }
            
            $key = $postMeta->getKey();
            $oldValue = get_post_meta($postId, $key, true);

            if (!isset($_POST[$key]) && $postMeta->getType() !== 'checkbox') {
                continue;
            }

            $value = isset($_POST[$key]) ? $_POST[$key] : '';
            if ($postMeta->getType() === 'checkbox') {
                $value = !empty($_POST[$key]) ? '1' : '0';
            }

            $postMeta->beforeSave($postId, $value, $oldValue);
            
            if ($postMeta->getArgs()['sanitize_callback']) {
                $value = call_user_func($postMeta->getArgs()['sanitize_callback'], $value);
            }
            
            if ($value !== false && $value !== null) {
                update_post_meta($postId, $key, $value);
                $postMeta->afterSave($postId, $value, $oldValue);
            }
        }
    }
} 