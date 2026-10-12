<?php
/**
 * Plugin Name: S2J MLDC Studio smoke seed
 * Description: After s2j-media-library-date-corrector is installed, adds one mismatched attachment for manual smoke tests.
 *
 * Source of truth for editing; keep in sync with smoke-test-blueprint.json writeFile body.
 *
 * @package S2J_MLDC
 */

add_action(
	'admin_init',
	static function (): void {
		if ( ! is_admin() || ! current_user_can( 'upload_files' ) ) {
			return;
		}

		if ( get_option( 's2j_mldc_studio_smoke_seeded' ) ) {
			return;
		}

		$plugin = 's2j-media-library-date-corrector/s2j-media-library-date-corrector.php';
		if ( ! is_plugin_active( $plugin ) ) {
			return;
		}

		$attachment_id = wp_insert_post(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'MLDC smoke mismatch sample',
				'post_date'      => '2019-06-01 00:00:00',
				'post_date_gmt'  => get_gmt_from_date( '2019-06-01 00:00:00' ),
			),
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return;
		}

		update_post_meta( $attachment_id, '_wp_attached_file', '2024/01/mldc-smoke-sample.jpg' );
		update_option( 's2j_mldc_studio_smoke_seeded', 1, false );
	}
);
