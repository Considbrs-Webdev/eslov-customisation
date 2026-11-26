<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

use WP_User;

/**
 * Choose which users get the "revision submitted" e-mail from Revisionary.
 *
 * Revisionary's classic notifications go to every administrator and editor who can edit the post, which
 * is far too many recipients. Administrators tick "Receive notifications about submitted revisions" on a
 * user's profile; only ticked users are notified. If nobody is ticked, Revisionary's default recipients
 * are kept so submissions never go unnoticed.
 *
 * The setting is stored per site (the meta key is prefixed with the site's table prefix), so each
 * subsite in the network has its own list. Only administrators can see or change it.
 */
class RevisionNotificationRecipients
{
    private const FIELD = 'eslov_revision_notifications';

    private const META_KEY = 'eslov_revision_notifications';

    public function __construct()
    {
        add_filter('revisionary_submission_notify_admin', [$this, 'filterRecipients']);
        add_action('show_user_profile', [$this, 'renderField']);
        add_action('edit_user_profile', [$this, 'renderField']);
        add_action('personal_options_update', [$this, 'saveField']);
        add_action('edit_user_profile_update', [$this, 'saveField']);
    }

    /**
     * Replace Revisionary's recipients with the users who opted in, if any.
     *
     * @param mixed $recipientIds User IDs (Revisionary may pass IDs as values or as keys).
     * @return mixed
     */
    public function filterRecipients($recipientIds)
    {
        $selected = get_users([
            'meta_key' => $this->metaKey(),
            'meta_value' => '1',
            'fields' => 'ID',
        ]);

        if (empty($selected)) {
            return $recipientIds;
        }

        return array_map('intval', $selected);
    }

    public function renderField(WP_User $user): void
    {
        if (!$this->currentUserMayEdit()) {
            return;
        }

        $checked = get_user_meta($user->ID, $this->metaKey(), true) === '1';

        wp_nonce_field(self::FIELD, self::FIELD . '_nonce');
        ?>
        <h2><?php esc_html_e('Revision notifications', 'eslov-customisation'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Notifications', 'eslov-customisation'); ?></th>
                <td>
                    <label for="<?php echo esc_attr(self::FIELD); ?>">
                        <input type="checkbox" name="<?php echo esc_attr(self::FIELD); ?>" id="<?php echo esc_attr(self::FIELD); ?>" value="1" <?php checked($checked); ?>>
                        <?php esc_html_e('Receive an e-mail when a revision is submitted for review', 'eslov-customisation'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('If nobody is selected, all administrators and editors are notified.', 'eslov-customisation'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function saveField(int $userId): void
    {
        if (
            !$this->currentUserMayEdit()
            || !isset($_POST[self::FIELD . '_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::FIELD . '_nonce'])), self::FIELD)
        ) {
            return;
        }

        if (!empty($_POST[self::FIELD])) {
            update_user_meta($userId, $this->metaKey(), '1');
        } else {
            delete_user_meta($userId, $this->metaKey());
        }
    }

    /**
     * Administrators only. Not a capability check: the revisor and editor roles hold manage_options here.
     */
    private function currentUserMayEdit(): bool
    {
        $user = wp_get_current_user();

        return $user->exists() && (is_super_admin($user->ID) || in_array('administrator', (array) $user->roles, true));
    }

    private function metaKey(): string
    {
        global $wpdb;

        return $wpdb->get_blog_prefix() . self::META_KEY;
    }
}
