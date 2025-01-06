<?php

namespace DiplomaEducation\Evenement\PostMeta\Entity;

use BackTo\Framework\PostMeta\Entity\PostMeta;
use DiplomaEducation\Evenement\PostType\EventPostType;

class EventEndTimePostMeta extends PostMeta
{
    const KEY = 'event_end_time';

    public function __construct(string $pluginTextDomain)
    {
        $this->setKey(self::KEY)
            ->setLabel(__('Event End Time', $pluginTextDomain))
            ->setType('time')
            ->addPostType(EventPostType::KEY)
            ->setArgs([
                'description' => __('End time of the event', $pluginTextDomain),
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => [$this, 'sanitizeTime'],
                'auth_callback' => [$this, 'authCallback'],
                'show_in_admin_column' => true,
                'meta_box_position' => 'side',
                'meta_box_priority' => 'default',
                'step' => '300', // 5 minutes steps
                'custom_render' => [$this, 'renderCustomMetaBox']
            ]);
    }

    public function renderCustomMetaBox($post, $metabox): void
    {
        $value = get_post_meta($post->ID, '_' . $this->getKey(), true);
        wp_nonce_field($this->getKey() . '_nonce', $this->getKey() . '_nonce');
        ?>
        <div class="components-base-control">
            <div class="components-base-control__field">
                <label class="components-base-control__label" for="<?php echo esc_attr($this->getKey()); ?>">
                    <?php _e('End Time', 'diploma-evenements'); ?>
                </label>
                <input
                    type="time"
                    id="<?php echo esc_attr($this->getKey()); ?>"
                    name="<?php echo esc_attr($this->getKey()); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    class="components-text-control__input"
                    step="<?php echo esc_attr($this->getArgs()['step']); ?>"
                />
            </div>
        </div>
        <?php
    }

    public function sanitizeTime($time): ?string
    {
        if (empty($time)) {
            return null;
        }

        if (preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return date('H:i', strtotime($time));
        }

        return null;
    }

    public function authCallback($allowed, $meta_key, $post_id, $user_id): bool
    {
        return current_user_can('edit_post', $post_id);
    }

    public function afterSave(int $post_id, $value, $old_value): void
    {
        if (!empty($value)) {
            $term_name = date('H:i', strtotime($value));
            wp_set_object_terms($post_id, $term_name, self::KEY);
        }
    }
} 