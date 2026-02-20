<?php
/**
 * Uninstall Script
 *
 * @package TopBar Buddy
 * @since 1.0.0
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Local variables in this file are function-scoped, not global

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Per-banner option names (stored with suffix '' for banner 1, '_2', '_3', etc. for additional banners).
$topbar_buddy_per_banner_options = array(
	'eeab_hide_banner',
	'topbar_buddy_prepend_element',
	'topbar_buddy_font_size',
	'topbar_buddy_color',
	'topbar_buddy_text_color',
	'topbar_buddy_link_color',
	'topbar_buddy_close_color',
	'topbar_buddy_text',
	'topbar_buddy_custom_css',
	'topbar_buddy_scrolling_custom_css',
	'topbar_buddy_text_custom_css',
	'topbar_buddy_button_css',
	'topbar_buddy_position',
	'topbar_buddy_z_index',
	'eeab_disabled_on_posts',
	'eeab_disabled_pages_array',
	'disabled_pages_array',
	'eeab_close_button_enabled',
	'eeab_close_button_expiration',
	'topbar_buddy_start_after_date',
	'topbar_buddy_remove_after_date',
	'topbar_buddy_insert_inside_element',
	'topbar_buddy_disabled_page_paths',
	'eeab_wp_body_open_enabled',
);

$topbar_buddy_banner_suffixes = array( '', '_2', '_3', '_4', '_5' );

foreach ( $topbar_buddy_per_banner_options as $topbar_buddy_option ) {
	foreach ( $topbar_buddy_banner_suffixes as $topbar_buddy_suffix ) {
		\delete_option( $topbar_buddy_option . $topbar_buddy_suffix );
	}
}

// Global options (no banner suffix).
$topbar_buddy_global_options = array(
	'topbar_buddy_debug_mode',
	'topbar_buddy_clear_cache',
	'topbar_buddy_migrated_to_multi_banner',
);

foreach ( $topbar_buddy_global_options as $topbar_buddy_option ) {
	\delete_option( $topbar_buddy_option );
}
