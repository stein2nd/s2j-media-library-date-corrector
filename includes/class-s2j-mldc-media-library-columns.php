<?php

/**
 * Media library list table extensions.
 *
 * @package S2J_MLDC
 */

namespace S2J\MLDC;

/**
 * Columns, bulk actions, row actions, and mismatch notice.
 */
class MediaLibraryColumns
{
    /**
     * Bulk action slug.
     */
    public const BULK_ACTION = 's2j_mldc_date_correct';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('manage_media_columns', array( $this, 'addColumns' ));
        add_action('manage_media_custom_column', array( $this, 'renderColumn' ), 10, 2);
        add_filter('bulk_actions-upload', array( $this, 'registerBulkAction' ));
        add_filter('media_row_actions', array( $this, 'registerRowAction' ), 10, 2);
        add_action('restrict_manage_posts', array( $this, 'renderCorrectAllButton' ));
        add_action('admin_notices', array( $this, 'maybeRenderMismatchNotice' ));
    }

    /**
     * Add custom columns after parent.
     *
     * @param string[] $columns Column headers.
     * @return string[]
     */
    public function addColumns(array $columns): array
    {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[ $key ] = $label;
            if ('parent' === $key) {
                $new['s2j_mldc_path_ym'] = __('Year/month (path)', 's2j-media-library-date-corrector');
                $new['s2j_mldc_diff']    = __('Difference', 's2j-media-library-date-corrector');
            }
        }

        if (! isset($new['s2j_mldc_path_ym'])) {
            $new['s2j_mldc_path_ym'] = __('Year/month (path)', 's2j-media-library-date-corrector');
            $new['s2j_mldc_diff']    = __('Difference', 's2j-media-library-date-corrector');
        }

        return $new;
    }

    /**
     * Render custom column cells.
     *
     * @param string $column_name Column key.
     * @param int    $post_id     Attachment ID.
     * @return void
     */
    public function renderColumn(string $column_name, int $post_id): void
    {
        if ('s2j_mldc_path_ym' === $column_name) {
            $row = MediaDateService::getRowData($post_id);
            echo esc_html($row['pathYm'] ?? '—');
            return;
        }

        if ('s2j_mldc_diff' === $column_name) {
            $row    = MediaDateService::getRowData($post_id);
            $labels = array(
                'match'    => __('Match', 's2j-media-library-date-corrector'),
                'mismatch' => __('Mismatch', 's2j-media-library-date-corrector'),
                'unknown'  => __('Unknown', 's2j-media-library-date-corrector'),
            );
            $label  = $labels[ $row['status'] ] ?? $row['status'];
            echo esc_html($label);
        }
    }

    /**
     * Register bulk action label.
     *
     * @param array<string,string> $actions Bulk actions.
     * @return array<string,string>
     */
    public function registerBulkAction(array $actions): array
    {
        $actions[ self::BULK_ACTION ] = __('Date Correct', 's2j-media-library-date-corrector');
        return $actions;
    }

    /**
     * Register row action link (handled by admin JS).
     *
     * @param array<string,string> $actions Row actions.
     * @param \WP_Post             $post    Attachment post.
     * @return array<string,string>
     */
    public function registerRowAction(array $actions, \WP_Post $post): array
    {
        if ('attachment' !== $post->post_type || ! current_user_can('upload_files')) {
            return $actions;
        }

        $actions['s2j_mldc_date_correct'] = sprintf(
            '<a href="#" class="s2j-mldc-date-correct-row" data-attachment-id="%d">%s</a>',
            (int) $post->ID,
            esc_html__('Date Correct', 's2j-media-library-date-corrector')
        );

        return $actions;
    }

    /**
     * Output Date Correct (All) button on upload.php.
     *
     * @param string $post_type Current post type.
     * @return void
     */
    public function renderCorrectAllButton(string $post_type): void
    {
        if ('attachment' !== $post_type || ! current_user_can('upload_files')) {
            return;
        }

        echo '<span class="s2j-mldc-correct-all-wrap">';
        printf(
            '<button type="button" class="button s2j-mldc-date-correct-all" id="s2j-mldc-date-correct-all">%s</button>',
            esc_html__('Date Correct (All)', 's2j-media-library-date-corrector')
        );
        echo '</span>';
    }

    /**
     * Show mismatch banner when option allows and current page has mismatches.
     *
     * @return void
     */
    public function maybeRenderMismatchNotice(): void
    {
        global $pagenow;

        if ('upload.php' !== $pagenow || ! current_user_can('upload_files')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list view mode check.
        if (isset($_GET['mode']) && 'grid' === $_GET['mode']) {
            return;
        }

        if (false === Settings::shouldShowMismatchNotice()) {
            return;
        }

        if (! $this->currentListPageHasMismatch()) {
            return;
        }

        printf(
            '<div class="notice notice-info s2j-mldc-mismatch-banner"><p>%s</p></div>',
            esc_html__(
                'File path year/month may not match attachment dates. Check Difference and use Date Correct.',
                's2j-media-library-date-corrector'
            )
        );
    }

    /**
     * Whether any attachment on the current list page is mismatch.
     *
     * @return bool
     */
    private function currentListPageHasMismatch(): bool
    {
        $query_args = Plugin::getCurrentUploadQueryArgs();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list pagination.
        $paged    = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);
        $per_page = (int) get_user_option('upload_per_page');
        if ($per_page < 1) {
            $per_page = 20;
        }

        $args = MediaDateService::buildAttachmentQuery(
            $query_args,
            ( $paged - 1 ) * $per_page,
            $per_page
        );

        foreach ($args->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }
            $row = MediaDateService::getRowData((int) $post->ID);
            if ('mismatch' === $row['status']) {
                return true;
            }
        }

        return false;
    }
}
