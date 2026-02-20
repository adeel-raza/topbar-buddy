<?php
/**
 * @since             1.0.0
 * @package           TopBar Buddy
 *
 * Plugin Name:       TopBar Buddy - Announcement Bar, Notification Bar and Sticky Alert Bar
 * Plugin URI:        https://wordpress.org/plugins/topbar-buddy/
 * Description:       Display announcement bars, notification bars, and sticky top banners in WordPress with scheduling, start/end dates, and page targeting
 * Version:           1.1.0
 * Author:            eLearning evolve
 * Author URI:        https://elearningevolve.com/about/
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       topbar-buddy
 * Requires PHP:      7.4
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.9
 */
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

defined( 'EEAB_MIN_WP' ) || define( 'EEAB_MIN_WP', '6.0' );
defined( 'EEAB_MIN_PHP' ) || define( 'EEAB_MIN_PHP', '7.4' );
defined( 'EEAB_VERSION' ) || define( 'EEAB_VERSION', '1.1.0' );
defined( 'EEAB_PLUGIN_FILE' ) || define( 'EEAB_PLUGIN_FILE', __FILE__ );
defined( 'EEAB_PLUGIN_DIR_PATH' ) || define( 'EEAB_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
defined( 'EEAB_PLUGIN_DIR_URL' ) || define( 'EEAB_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

require EEAB_PLUGIN_DIR_PATH . 'compat.php';

if (
	version_compare( $GLOBALS['wp_version'], EEAB_MIN_WP, '<' )
	||
	version_compare( phpversion(), EEAB_MIN_PHP, '<' )
) {
	require EEAB_PLUGIN_DIR_PATH . 'old-versions.php';
} else {
	require EEAB_PLUGIN_DIR_PATH . 'class.topbar-buddy.php';
}


// Initialize the plugin if requirements are met.
if (
	version_compare( $GLOBALS['wp_version'], EEAB_MIN_WP, '>=' ) &&
	version_compare( phpversion(), EEAB_MIN_PHP, '>=' )
) {
	if ( class_exists( 'ElearningEvolve\TopBarBuddy\Plugin' ) ) {
		new ElearningEvolve\TopBarBuddy\Plugin();
	}
}


