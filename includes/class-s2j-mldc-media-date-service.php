<?php

/**
 * Media date extraction, comparison, and correction.
 *
 * @package S2J_MLDC
 */

namespace S2J\MLDC;

/**
 * Core service for path-based date correction.
 */
class MediaDateService
{
    /**
     * Path prefix pattern for yyyy/mm/.
     */
    private const PATH_PATTERN = '/^(\d{4})\/(0[1-9]|1[0-2])\//';

    /**
     * Extract year/month from _wp_attached_file.
     *
     * @param string|null $attached_file Relative upload path.
     * @return array{year:int,month:int,label:string}|null
     */
    public static function extractPathYm(?string $attached_file): ?array
    {
        if (empty($attached_file)) {
            return null;
        }

        if (! preg_match(self::PATH_PATTERN, $attached_file, $matches)) {
            return null;
        }

        $year  = (int) $matches[1];
        $month = (int) $matches[2];

        if ($year < 1000 || $year > 9999) {
            return null;
        }

        return array(
            'year'  => $year,
            'month' => $month,
            'label' => sprintf('%04d/%02d', $year, $month),
        );
    }

    /**
     * Extract yyyy/mm from post_date.
     *
     * @param string|null $post_date Post date string.
     * @return string|null
     */
    public static function extractPostDateYm(?string $post_date): ?string
    {
        if (empty($post_date)) {
            return null;
        }

        $timestamp = strtotime($post_date);
        if (false === $timestamp) {
            return null;
        }

        return gmdate('Y/m', $timestamp);
    }

    /**
     * Mismatch status for list columns.
     *
     * @param int $attachment_id Attachment ID.
     * @return array{pathYm:?string,postDateYm:?string,status:string}
     */
    public static function getRowData(int $attachment_id): array
    {
        $attached = (string) get_post_meta($attachment_id, '_wp_attached_file', true);
        $post     = get_post($attachment_id);
        $post_ym  = $post instanceof \WP_Post ? self::extractPostDateYm($post->post_date) : null;
        $path     = self::extractPathYm($attached);
        $path_ym  = $path ? $path['label'] : null;

        if (null === $path_ym) {
            $status = 'unknown';
        } elseif (null === $post_ym || $path_ym !== $post_ym) {
            $status = 'mismatch';
        } else {
            $status = 'match';
        }

        return array(
            'pathYm'     => $path_ym,
            'postDateYm' => $post_ym,
            'status'     => $status,
        );
    }

    /**
     * Correct a list of attachment IDs.
     *
     * @param int[] $ids Attachment IDs (unique).
     * @return array{status:string,summary:array,results:array}
     */
    public static function correctIds(array $ids): array
    {
        $ids     = array_values(array_unique(array_map('intval', $ids)));
        $total   = count($ids);
        $results = array();

        foreach ($ids as $id) {
            $results[] = self::correctSingleId($id);
        }

        return self::buildApiResponse($results, $total, $total);
    }

    /**
     * Correct attachments matching a media-library query window.
     *
     * @param array<string,mixed> $query_args Allowed list filters.
     * @param int                 $offset     Scan offset.
     * @return array{status:string,summary:array,results:array,nextOffset:?int}
     */
    public static function correctQuery(array $query_args, int $offset = 0): array
    {
        $offset     = max(0, $offset);
        $scan_limit = S2J_MLDC_CHUNK_SIZE;
        $query      = self::buildAttachmentQuery($query_args, $offset, $scan_limit);
        $posts      = $query->posts;
        $scanned    = count($posts);
        $results    = array();

        foreach ($posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $attached = (string) get_post_meta($post->ID, '_wp_attached_file', true);
            if (null === self::extractPathYm($attached)) {
                continue;
            }

            $results[] = self::correctSingleId((int) $post->ID);
        }

        $total     = count($results);
        $processed = $total;
        $response  = self::buildApiResponse($results, $total, $processed);

        if ($scanned < $scan_limit) {
            $response['nextOffset'] = null;
        } else {
            $response['nextOffset'] = $offset + $scanned;
        }

        return $response;
    }

    /**
     * Build WP_Query for media library parity.
     *
     * @param array<string,mixed> $query_args Client query object.
     * @param int                 $offset     Offset.
     * @param int                 $per_page   Posts per page.
     * @return \WP_Query
     */
    public static function buildAttachmentQuery(array $query_args, int $offset, int $per_page): \WP_Query
    {
        $args = array(
            'post_type'              => 'attachment',
            'post_status'            => 'inherit',
            'posts_per_page'         => $per_page,
            'offset'                 => $offset,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if (! empty($query_args['s'])) {
            $args['s'] = sanitize_text_field((string) $query_args['s']);
        }

        $m = isset($query_args['m']) ? (string) $query_args['m'] : '0';
        if ('' !== $m && '0' !== $m && ctype_digit($m) && strlen($m) >= 6) {
            $args['year']     = (int) substr($m, 0, 4);
            $args['monthnum'] = (int) substr($m, 4, 2);
        }

        if (! empty($query_args['post_mime_type'])) {
            $args['post_mime_type'] = sanitize_text_field((string) $query_args['post_mime_type']);
        }

        if (! empty($query_args['author'])) {
            $args['author'] = (int) $query_args['author'];
        }

        $allowed_orderby = array( 'date', 'title', 'filename' );
        if (! empty($query_args['orderby'])) {
            $orderby = sanitize_key((string) $query_args['orderby']);
            if (in_array($orderby, $allowed_orderby, true)) {
                $args['orderby'] = $orderby;
            }
        }

        if (! empty($query_args['order'])) {
            $order = strtoupper(sanitize_key((string) $query_args['order']));
            if ('ASC' === $order || 'DESC' === $order) {
                $args['order'] = $order;
            }
        }

        return new \WP_Query($args);
    }

    /**
     * Correct one attachment.
     *
     * @param int $id Attachment ID.
     * @return array{id:int,status:string,message?:string}
     */
    private static function correctSingleId(int $id): array
    {
        $post = get_post($id);

        if (! $post instanceof \WP_Post || 'attachment' !== $post->post_type) {
            return array(
                'id'      => $id,
                'status'  => 'error',
                'message' => __('Target media was not found.', 's2j-media-library-date-corrector'),
            );
        }

        if (! current_user_can('edit_post', $id)) {
            return array(
                'id'      => $id,
                'status'  => 'error',
                'message' => __('You do not have permission to edit this item.', 's2j-media-library-date-corrector'),
            );
        }

        $attached = (string) get_post_meta($id, '_wp_attached_file', true);
        $path     = self::extractPathYm($attached);

        if (null === $path) {
            return array(
                'id'      => $id,
                'status'  => 'skipped',
                'message' => __(
                    'Could not read year/month from the file path; skipping correction.',
                    's2j-media-library-date-corrector'
                ),
            );
        }

        $post_ym = self::extractPostDateYm($post->post_date);
        $path_ym = $path['label'];

        if (null !== $post_ym && $path_ym === $post_ym) {
            return array(
                'id'      => $id,
                'status'  => 'skipped',
                'message' => __('The date already matches the path year/month.', 's2j-media-library-date-corrector'),
            );
        }

        $new_date = sprintf('%04d-%02d-01 00:00:00', $path['year'], $path['month']);
        $new_gmt  = get_gmt_from_date($new_date);

        $updated = wp_update_post(
            array(
                'ID'                => $id,
                'post_date'         => $new_date,
                'post_date_gmt'     => $new_gmt,
                'post_modified'     => $post->post_modified,
                'post_modified_gmt' => $post->post_modified_gmt,
            ),
            true
        );

        if (is_wp_error($updated)) {
            return array(
                'id'      => $id,
                'status'  => 'error',
                'message' => $updated->get_error_message(),
            );
        }

        return array(
            'id'      => $id,
            'status'  => 'success',
            'message' => __('Date corrected.', 's2j-media-library-date-corrector'),
        );
    }

    /**
     * Aggregate result items into API response shape.
     *
     * @param array<int,array<string,mixed>> $results   Per-item results.
     * @param int                            $total     Request total.
     * @param int                            $processed Processed count.
     * @return array{status:string,summary:array,results:array}
     */
    public static function buildApiResponse(array $results, int $total, int $processed): array
    {
        $success = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($results as $item) {
            switch ($item['status']) {
                case 'success':
                    ++$success;
                    break;
                case 'skipped':
                    ++$skipped;
                    break;
                case 'error':
                    ++$failed;
                    break;
                default:
                    break;
            }
        }

        if ($processed < $total) {
            $status = 'partial';
        } elseif ($failed > 0 && 0 === $success && 0 === $skipped) {
            $status = 'error';
        } elseif ($failed > 0) {
            $status = 'partial';
        } else {
            $status = 'success';
        }

        return array(
            'status'  => $status,
            'summary' => array(
                'total'     => $total,
                'processed' => $processed,
                'success'   => $success,
                'skipped'   => $skipped,
                'failed'    => $failed,
            ),
            'results' => $results,
        );
    }
}
