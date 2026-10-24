<?php
/**
 * S2J Media Library Date Corrector
 *
 * @package S2J_MLDC
 * @author Koutarou ISHIKAWA
 * @copyright 2025 Koutarou ISHIKAWA
 * @license GPL v3 or later
 *
 * Plugin Name: S2J Media Library Date Corrector
 * Plugin URI: https://github.com/stein2nd/s2j-media-library-date-corrector
 * Description: Designed to resolve metadata inconsistencies that occur after bulk media uploads in WordPress. Correct attachment post_date values from uploads path year/month.
 * Version: 1.0.11
 * Author: Koutarou ISHIKAWA
 * Author URI: https://stein2nd.wordpress.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: s2j-media-library-date-corrector
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'S2J_MLDC_VERSION', '1.0.11' );
define( 'S2J_MLDC_PLUGIN_FILE', __FILE__ );
define( 'S2J_MLDC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'S2J_MLDC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'S2J_MLDC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'S2J_MLDC_REST_NAMESPACE', 's2j-mldc/v1' );
define( 'S2J_MLDC_CHUNK_SIZE', 100 );

require_once S2J_MLDC_PLUGIN_DIR . 'includes/class-s2j-mldc-media-date-service.php';
require_once S2J_MLDC_PLUGIN_DIR . 'includes/class-s2j-mldc-rest-controller.php';
require_once S2J_MLDC_PLUGIN_DIR . 'includes/class-s2j-mldc-media-library-columns.php';
require_once S2J_MLDC_PLUGIN_DIR . 'includes/class-s2j-mldc-settings.php';
require_once S2J_MLDC_PLUGIN_DIR . 'includes/class-s2j-mldc-plugin.php';

\S2J\MLDC\Plugin::getInstance();
