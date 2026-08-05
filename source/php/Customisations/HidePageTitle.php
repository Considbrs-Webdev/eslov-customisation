<?php

/**
 * "Hide title" checkbox for pages and Municipio postObject filtering.
 */

namespace EslovCustomisation\Customisations;

class HidePageTitle
{
    private const META_KEY = 'modularity-module-hide-title';

    private const NONCE_ACTION = 'modularity_hide_title';

    private const NONCE_FIELD = 'modularity_hide_title_nonce';

    public function __construct()
    {
        add_action('edit_form_before_permalink', [$this, 'renderCheckbox']);
        add_action('save_post', [$this, 'save'], 10, 2);
        add_filter('Municipio/Helper/Post/postObject', [$this, 'filterPostObject']);
    }

    /**
     * Render the hide-title checkbox on the page edit screen.
     */
    public function renderCheckbox(): void
    {
        global $post;

        if (!$post instanceof \WP_Post || $post->post_type !== 'page') {
            return;
        }

        $hidden = (bool) get_post_meta($post->ID, self::META_KEY, true);

        echo '<div style="margin: 20px 0;">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        printf(
            '<label style="cursor:pointer;"><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(self::META_KEY),
            checked(true, $hidden, false),
            esc_html__('Hide title', 'eslov-customisation')
        );
        echo '</div>';
    }

    /**
     * Persist the hide-title checkbox value.
     */
    public function save(int $postId, \WP_Post $post): void
    {
        if ($post->post_type !== 'page') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postId)) {
            return;
        }

        if (
            !isset($_POST[self::NONCE_FIELD])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION)
        ) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        update_post_meta($postId, self::META_KEY, isset($_POST[self::META_KEY]) ? '1' : '0');
    }

    /**
     * Strip the title from the Municipio post object when hide-title is enabled.
     *
     * @param object $postObject
     * @return object
     */
    public function filterPostObject(object $postObject)
    {
        if (!isset($postObject->ID) || !is_page($postObject->ID)) {
            return $postObject;
        }

        if (!get_post_meta($postObject->ID, self::META_KEY, true)) {
            return $postObject;
        }

        $postObject->post_title_filtered = '';
        $postObject->post_title = '';

        return $postObject;
    }
}
