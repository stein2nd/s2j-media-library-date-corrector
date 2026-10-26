<?php

/**
 * Plugin bootstrap.
 *
 * @package S2J_MLDC
 */

namespace S2J\MLDC;

/**
 * Main plugin singleton.
 */
class Plugin
{
    /**
     * Instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Get singleton.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        add_action('plugins_loaded', array( $this, 'loadTextdomain' ));
        add_action('init', array( $this, 'init' ));
        add_action('admin_enqueue_scripts', array( $this, 'adminEnqueueScripts' ));
    }

    /**
     * Load translations.
     *
     * @return void
     */
    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            's2j-media-library-date-corrector',
            false,
            dirname(S2J_MLDC_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize components.
     *
     * @return void
     */
    public function init(): void
    {
        new RestController();

        if (is_admin()) {
            new MediaLibraryColumns();
            new Settings();
        }
    }

    /**
     * Enqueue admin assets on media library list view.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function adminEnqueueScripts(string $hook): void
    {
        if ('upload.php' !== $hook) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list view mode check.
        if (isset($_GET['mode']) && 'grid' === $_GET['mode']) {
            return;
        }

        if (! current_user_can('upload_files')) {
            return;
        }

        $script_path = S2J_MLDC_PLUGIN_DIR . 'dist/js/s2j-media-library-date-corrector-settings.js';
        $style_path  = S2J_MLDC_PLUGIN_DIR . 'dist/css/s2j-media-library-date-corrector-settings.css';

        if (! file_exists($script_path)) {
            return;
        }

        wp_enqueue_script(
            's2j-media-library-date-corrector-admin',
            S2J_MLDC_PLUGIN_URL . 'dist/js/s2j-media-library-date-corrector-settings.js',
            array( 'wp-api-fetch', 'wp-api', 'wp-element', 'wp-i18n', 'react', 'react-dom' ),
            S2J_MLDC_VERSION,
            true
        );

        if (file_exists($style_path)) {
            wp_enqueue_style(
                's2j-media-library-date-corrector-admin',
                S2J_MLDC_PLUGIN_URL . 'dist/css/s2j-media-library-date-corrector-settings.css',
                array(),
                S2J_MLDC_VERSION
            );
        }

        wp_localize_script(
            's2j-media-library-date-corrector-admin',
            's2jMldcConfig',
            array(
                'restNamespace' => S2J_MLDC_REST_NAMESPACE,
                'chunkSize'     => S2J_MLDC_CHUNK_SIZE,
                'bulkAction'    => MediaLibraryColumns::BULK_ACTION,
                'query'         => self::getCurrentUploadQueryArgs(),
                'strings'       => array(
                    'processing'      => __('Processing…', 's2j-media-library-date-corrector'),
                    /* translators: %d: number of items being processed. */
                    'processingCount' => __('%d items processing…', 's2j-media-library-date-corrector'),
                    'confirmAll'      => __(
                        'Correct dates for all items matching the current search and filters?',
                        's2j-media-library-date-corrector'
                    ),
                    'retryFailed'     => __('Retry Failed', 's2j-media-library-date-corrector'),
                ),
            )
        );

        wp_set_script_translations(
            's2j-media-library-date-corrector-admin',
            's2j-media-library-date-corrector',
            S2J_MLDC_PLUGIN_DIR . 'languages'
        );
    }

    /**
     * Read upload.php list filters for REST correct-query.
     *
     * @return array<string,mixed>
     */
    public static function getCurrentUploadQueryArgs(): array
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only mirror of list filters.
        $get = wp_unslash($_GET);

        return array(
            's'              => isset($get['s']) ? sanitize_text_field((string) $get['s']) : '',
            'm'              => isset($get['m']) ? sanitize_text_field((string) $get['m']) : '0',
            'post_mime_type' => isset($get['post_mime_type'])
                ? sanitize_text_field((string) $get['post_mime_type'])
                : '',
            'author'         => isset($get['author']) ? (int) $get['author'] : 0,
            'orderby'        => isset($get['orderby']) ? sanitize_key((string) $get['orderby']) : 'date',
            'order'          => isset($get['order']) ? sanitize_key((string) $get['order']) : 'desc',
        );
    }
}
