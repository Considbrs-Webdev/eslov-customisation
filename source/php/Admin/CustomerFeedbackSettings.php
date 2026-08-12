<?php

declare(strict_types=1);

namespace EslovCustomisation\Admin;

/**
 * Settings page for controlling where the customer feedback form is hidden.
 */
class CustomerFeedbackSettings
{
    private const PAGE_SLUG = 'eslov-customisation-customer-feedback';
    private const OPTION_GROUP = 'eslov_customisation_customer_feedback';
    private const SETTINGS_PAGE = 'eslov_customisation_group_customer_feedback';

    public const OPTION_EXCLUDED_ARCHIVE_POST_TYPES = 'eslov_customisation_customer_feedback_excluded_archive_post_types';
    public const OPTION_EXCLUDED_CONTEXTS = 'eslov_customisation_customer_feedback_excluded_contexts';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPage(): void
    {
        add_options_page(
            __('Customer feedback', 'eslov-customisation'),
            __('Customer feedback', 'eslov-customisation'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_EXCLUDED_ARCHIVE_POST_TYPES,
            [
                'type'              => 'array',
                'default'           => [],
                'sanitize_callback' => [$this, 'sanitizeArchivePostTypes'],
                'show_in_rest'      => false,
            ]
        );

        register_setting(
            self::OPTION_GROUP,
            self::OPTION_EXCLUDED_CONTEXTS,
            [
                'type'              => 'array',
                'default'           => [],
                'sanitize_callback' => [$this, 'sanitizeContexts'],
                'show_in_rest'      => false,
            ]
        );

        add_settings_section(
            'eslov_customisation_customer_feedback',
            __('Visibility', 'eslov-customisation'),
            static function (): void {
                echo '<p>' . esc_html__(
                    'Control where the customer feedback form should be hidden. Front page and individually excluded singular posts/pages are already handled separately.',
                    'eslov-customisation'
                ) . '</p>';
            },
            self::SETTINGS_PAGE
        );

        add_settings_field(
            self::OPTION_EXCLUDED_ARCHIVE_POST_TYPES,
            __('Exclude on post type archives', 'eslov-customisation'),
            [$this, 'renderArchivePostTypesField'],
            self::SETTINGS_PAGE,
            'eslov_customisation_customer_feedback'
        );

        add_settings_field(
            self::OPTION_EXCLUDED_CONTEXTS,
            __('Exclude on other page types', 'eslov-customisation'),
            [$this, 'renderContextsField'],
            self::SETTINGS_PAGE,
            'eslov_customisation_customer_feedback'
        );
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Customer feedback settings', 'eslov-customisation'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::SETTINGS_PAGE);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function renderArchivePostTypesField(): void
    {
        $options     = self::getArchivePostTypeOptions();
        $selectedSet = array_flip(self::getExcludedArchivePostTypes());
        ?>
        <fieldset>
            <?php if ($options === []) : ?>
                <p class="description">
                    <?php esc_html_e('No public post types with archives were found.', 'eslov-customisation'); ?>
                </p>
            <?php else : ?>
                <?php foreach ($options as $slug => $label) : ?>
                    <label style="display:block;margin-bottom:0.35em;">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr(self::OPTION_EXCLUDED_ARCHIVE_POST_TYPES); ?>[]"
                            value="<?php echo esc_attr($slug); ?>"
                            <?php checked(isset($selectedSet[$slug])); ?>
                        />
                        <?php echo esc_html($label); ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
            <p class="description">
                <?php esc_html_e('When checked, the feedback form is hidden on archive pages for the selected post types.', 'eslov-customisation'); ?>
            </p>
        </fieldset>
        <?php
    }

    public function renderContextsField(): void
    {
        $options     = self::getContextOptions();
        $selectedSet = array_flip(self::getExcludedContexts());
        ?>
        <fieldset>
            <?php foreach ($options as $key => $label) : ?>
                <label style="display:block;margin-bottom:0.35em;">
                    <input
                        type="checkbox"
                        name="<?php echo esc_attr(self::OPTION_EXCLUDED_CONTEXTS); ?>[]"
                        value="<?php echo esc_attr($key); ?>"
                        <?php checked(isset($selectedSet[$key])); ?>
                    />
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
            <p class="description">
                <?php esc_html_e('Use these toggles to hide feedback on common non-singular or utility page types.', 'eslov-customisation'); ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    public function sanitizeArchivePostTypes($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return self::sanitizeSelectedValues($value, array_keys(self::getArchivePostTypeOptions()));
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    public function sanitizeContexts($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return self::sanitizeSelectedValues($value, array_keys(self::getContextOptions()));
    }

    /**
     * @return list<string>
     */
    public static function getExcludedArchivePostTypes(): array
    {
        $raw = get_option(self::OPTION_EXCLUDED_ARCHIVE_POST_TYPES, []);

        if (!is_array($raw)) {
            return [];
        }

        return self::sanitizeSelectedValues($raw, array_keys(self::getArchivePostTypeOptions()));
    }

    /**
     * @return list<string>
     */
    public static function getExcludedContexts(): array
    {
        $raw = get_option(self::OPTION_EXCLUDED_CONTEXTS, []);

        if (!is_array($raw)) {
            return [];
        }

        return self::sanitizeSelectedValues($raw, array_keys(self::getContextOptions()));
    }

    /**
     * @param array<int|string, mixed> $values
     * @param list<string>             $allowedKeys
     *
     * @return list<string>
     */
    private static function sanitizeSelectedValues(array $values, array $allowedKeys): array
    {
        $allowedSet = array_flip($allowedKeys);
        $resultSet  = [];

        foreach ($values as $value) {
            $key = sanitize_key((string) $value);
            if ($key !== '' && isset($allowedSet[$key])) {
                $resultSet[$key] = true;
            }
        }

        return array_keys($resultSet);
    }

    /**
     * @return array<string, string>
     */
    private static function getArchivePostTypeOptions(): array
    {
        $objects = get_post_types(
            [
                'public' => true,
            ],
            'objects'
        );

        $options = [];

        foreach ($objects as $slug => $object) {
            if (empty($object->has_archive)) {
                continue;
            }

            $label = $object->labels->name ?? $slug;
            $options[$slug] = (string) $label;
        }

        natcasesort($options);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function getContextOptions(): array
    {
        return [
            'home'     => __('Posts page (blog index)', 'eslov-customisation'),
            'search'   => __('Search results', 'eslov-customisation'),
            'taxonomy' => __('Taxonomy archives', 'eslov-customisation'),
            'category' => __('Category archives', 'eslov-customisation'),
            'tag'      => __('Tag archives', 'eslov-customisation'),
            'date'     => __('Date archives', 'eslov-customisation'),
            'author'   => __('Author archives', 'eslov-customisation'),
            '404'      => __('404 pages', 'eslov-customisation'),
        ];
    }
}
