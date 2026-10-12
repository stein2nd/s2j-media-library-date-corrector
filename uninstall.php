<?php
/**
 * Uninstall handler.
 *
 * @package S2J_MLDC
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 's2j_mldc_mismatch_notice' );
