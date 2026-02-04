<?php
/**
 * Banner Manager Class
 *
 * @package TopBar Buddy
 * @since 2.0.0
 */

namespace ElearningEvolve\TopBarBuddy;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Banner Manager class for handling multiple banners.
 */
class BannerManager {

	/**
	 * Table name for storing banners.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Initialize the banner manager.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'topbar_buddy_banners';
		$this->init();
	}

	/**
	 * Initialize hooks and create table if needed.
	 *
	 * @since 2.0.0
	 */
	private function init() {
		// Create table on plugin activation or when needed
		\add_action( 'init', array( $this, 'maybe_create_table' ) );
		
		// Migration hook to convert existing single banner to multi-banner system
		\add_action( 'init', array( $this, 'maybe_migrate_existing_banner' ) );
	}

	/**
	 * Create banners table if it doesn't exist.
	 *
	 * @since 2.0.0
	 */
	public function maybe_create_table() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table check, necessary for plugin functionality
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SELECT table_name FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
			DB_NAME,
			$this->table_name
		) );

		if ( $table_exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		// Table name is safely set in constructor from $wpdb->prefix
		$table_name_safe = esc_sql( $this->table_name );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dbDelta() requires table name in SQL string
		$sql = "CREATE TABLE `{$table_name_safe}` (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			content longtext NOT NULL,
			background_color varchar(7) DEFAULT '#000000',
			text_color varchar(7) DEFAULT '#ffffff',
			link_color varchar(7) DEFAULT '#f16521',
			close_color varchar(7) DEFAULT '#ffffff',
			font_size varchar(20) DEFAULT '',
			position varchar(20) DEFAULT 'fixed',
			z_index varchar(10) DEFAULT '999999',
			start_date datetime DEFAULT NULL,
			end_date datetime DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			disabled_on_posts tinyint(1) DEFAULT 0,
			disabled_pages text DEFAULT '',
			disabled_paths text DEFAULT '',
			close_button_enabled tinyint(1) DEFAULT 0,
			close_button_expiration int(11) DEFAULT 24,
			custom_css longtext DEFAULT '',
			scrolling_custom_css longtext DEFAULT '',
			text_custom_css longtext DEFAULT '',
			button_css longtext DEFAULT '',
			prepend_element varchar(255) DEFAULT '',
			insert_inside_element varchar(255) DEFAULT '',
			created_date datetime DEFAULT CURRENT_TIMESTAMP,
			modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Migrate existing single banner data to multi-banner system.
	 *
	 * @since 2.0.0
	 */
	public function maybe_migrate_existing_banner() {
		// Check if migration has already been done
		if ( \get_option( 'topbar_buddy_migrated_to_multi_banner' ) ) {
			return;
		}

		// Check if there's existing single banner data to migrate
		$existing_text = \get_option( 'topbar_buddy_text' );
		if ( empty( $existing_text ) ) {
			// No existing banner to migrate, mark as migrated
			\update_option( 'topbar_buddy_migrated_to_multi_banner', true );
			return;
		}

		// Migrate existing banner data
		$banner_data = array(
			'name' => __( 'Migrated Banner', 'topbar-buddy' ),
			'content' => $existing_text,
			'background_color' => \get_option( 'topbar_buddy_color', '#000000' ),
			'text_color' => \get_option( 'topbar_buddy_text_color', '#ffffff' ),
			'link_color' => \get_option( 'topbar_buddy_link_color', '#f16521' ),
			'close_color' => \get_option( 'topbar_buddy_close_color', '#ffffff' ),
			'font_size' => \get_option( 'topbar_buddy_font_size', '' ),
			'position' => \get_option( 'topbar_buddy_position', 'fixed' ),
			'z_index' => \get_option( 'topbar_buddy_z_index', '999999' ),
			'start_date' => \get_option( 'topbar_buddy_start_after_date' ),
			'end_date' => \get_option( 'topbar_buddy_remove_after_date' ),
			'is_active' => \get_option( 'eeab_hide_banner' ) !== 'yes' ? 1 : 0,
			'disabled_on_posts' => \get_option( 'eeab_disabled_on_posts' ) ? 1 : 0,
			'disabled_pages' => \get_option( 'eeab_disabled_pages_array', '' ),
			'disabled_paths' => \get_option( 'topbar_buddy_disabled_page_paths', '' ),
			'close_button_enabled' => \get_option( 'eeab_close_button_enabled' ) ? 1 : 0,
			'close_button_expiration' => \get_option( 'eeab_close_button_expiration', 24 ),
			'custom_css' => \get_option( 'topbar_buddy_custom_css', '' ),
			'scrolling_custom_css' => \get_option( 'topbar_buddy_scrolling_custom_css', '' ),
			'text_custom_css' => \get_option( 'topbar_buddy_text_custom_css', '' ),
			'button_css' => \get_option( 'topbar_buddy_button_css', '' ),
			'prepend_element' => \get_option( 'topbar_buddy_prepend_element', '' ),
			'insert_inside_element' => \get_option( 'topbar_buddy_insert_inside_element', '' ),
		);

		// Convert date formats
		if ( ! empty( $banner_data['start_date'] ) ) {
			try {
				$start_dt = new \DateTime( $banner_data['start_date'] );
				$banner_data['start_date'] = $start_dt->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $e ) {
				$banner_data['start_date'] = null;
			}
		} else {
			$banner_data['start_date'] = null;
		}

		if ( ! empty( $banner_data['end_date'] ) ) {
			try {
				$end_dt = new \DateTime( $banner_data['end_date'] );
				$banner_data['end_date'] = $end_dt->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $e ) {
				$banner_data['end_date'] = null;
			}
		} else {
			$banner_data['end_date'] = null;
		}

		// Create the migrated banner
		$this->create_banner( $banner_data );

		// Mark migration as complete
		\update_option( 'topbar_buddy_migrated_to_multi_banner', true );
	}

	/**
	 * Create a new banner.
	 *
	 * @since 2.0.0
	 * @param array $data Banner data.
	 * @return int|false Banner ID on success, false on failure.
	 */
	public function create_banner( $data ) {
		global $wpdb;

		$defaults = array(
			'name' => '',
			'content' => '',
			'background_color' => '#000000',
			'text_color' => '#ffffff',
			'link_color' => '#f16521',
			'close_color' => '#ffffff',
			'font_size' => '',
			'position' => 'fixed',
			'z_index' => '999999',
			'start_date' => null,
			'end_date' => null,
			'is_active' => 1,
			'disabled_on_posts' => 0,
			'disabled_pages' => '',
			'disabled_paths' => '',
			'close_button_enabled' => 0,
			'close_button_expiration' => 24,
			'custom_css' => '',
			'scrolling_custom_css' => '',
			'text_custom_css' => '',
			'button_css' => '',
			'prepend_element' => '',
			'insert_inside_element' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table operation, necessary for plugin functionality
		$result = $wpdb->insert(
			$this->table_name,
			$data,
			array(
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', // name, content, colors, font_size, position, z_index
				'%s', '%s', '%d', '%d', '%s', '%s', // start_date, end_date, is_active, disabled_on_posts, disabled_pages, disabled_paths
				'%d', '%d', '%s', '%s', '%s', '%s', // close_button_enabled, close_button_expiration, custom_css, scrolling_custom_css, text_custom_css, button_css
				'%s', '%s' // prepend_element, insert_inside_element
			)
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing banner.
	 *
	 * @since 2.0.0
	 * @param int   $banner_id Banner ID.
	 * @param array $data Banner data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update_banner( $banner_id, $data ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table operation, necessary for plugin functionality
		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $banner_id ),
			null,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete a banner.
	 *
	 * @since 2.0.0
	 * @param int $banner_id Banner ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_banner( $banner_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table operation, necessary for plugin functionality
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $banner_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get a banner by ID.
	 *
	 * @since 2.0.0
	 * @param int $banner_id Banner ID.
	 * @return object|null Banner object or null if not found.
	 */
	public function get_banner( $banner_id ) {
		global $wpdb;

		$table_name_safe = esc_sql( $this->table_name );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL
		// Custom table operation, table name safely escaped with esc_sql(), id is prepared with %d
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM `{$table_name_safe}` WHERE id = %d",
			absint( $banner_id )
		) );
		// phpcs:enable
	}

	/**
	 * Get all banners.
	 *
	 * @since 2.0.0
	 * @param array $args Query arguments.
	 * @return array Array of banner objects.
	 */
	public function get_banners( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'active_only' => false,
			'orderby' => 'created_date',
			'order' => 'ASC',
			'limit' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Whitelist orderby and order values for security
		$allowed_orderby = array( 'id', 'name', 'created_date', 'start_date', 'end_date' );
		$allowed_order = array( 'ASC', 'DESC' );
		
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_date';
		$order = in_array( strtoupper( $args['order'] ), $allowed_order, true ) ? strtoupper( $args['order'] ) : 'ASC';
		
		// Build safe SQL query - orderby and order are whitelisted AND escaped, table name is escaped
		$table_name_safe = esc_sql( $this->table_name );
		$orderby_safe = esc_sql( $orderby );
		$order_safe = esc_sql( $order );
		
		// Build query with placeholders
		$where_clause = $args['active_only'] ? ' WHERE is_active = %d' : '';
		$order_clause = " ORDER BY `{$orderby_safe}` {$order_safe}";
		
		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL,WordPress.NamingConventions.PrefixAllGlobals
		// Custom table operation, local variables $query and $sql are not globals
		if ( $args['limit'] ) {
			$query = "SELECT * FROM `{$table_name_safe}`{$where_clause}{$order_clause} LIMIT %d";
			if ( $args['active_only'] ) {
				$sql = $wpdb->prepare( $query, array( 1, absint( $args['limit'] ) ) );
			} else {
				$sql = $wpdb->prepare( $query, array( absint( $args['limit'] ) ) );
			}
		} else {
			$query = "SELECT * FROM `{$table_name_safe}`{$where_clause}{$order_clause}";
			if ( $args['active_only'] ) {
				$sql = $wpdb->prepare( $query, array( 1 ) );
			} else {
				$sql = $wpdb->prepare( $query, array() );
			}
		}

	return $wpdb->get_results( $sql ); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
/**
 * Get active banners that should be displayed at the current time.
 *
 * @since 2.0.0
 * @return array Array of banner objects that should be displayed.
 */
public function get_current_active_banners() {
	global $wpdb;

	// Get WordPress timezone
	$timezone_string = \get_option( 'timezone_string' );
	if ( ! empty( $timezone_string ) ) {
		try {
			$wp_timezone = new \DateTimeZone( $timezone_string );
		} catch ( \Exception $e ) {
			$wp_timezone = new \DateTimeZone( 'UTC' );
		}
	} else {
		$wp_timezone = new \DateTimeZone( 'UTC' );
	}

	$current_time = new \DateTime( 'now', $wp_timezone );
	$current_time_str = $current_time->format( 'Y-m-d H:i:s' );

	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	// Local variables inside function, not globals - no prefix needed
	// Table name is set in constructor and never changes, safe to use directly
	$table_name_safe = esc_sql( $this->table_name );
	// Build SQL template with safely escaped table name, then prepare with placeholders
	$sql_template = "SELECT * FROM `{$table_name_safe}` 
			WHERE is_active = 1 
			AND (start_date IS NULL OR start_date <= %s)
			AND (end_date IS NULL OR end_date > %s)
			ORDER BY created_date ASC";

	// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL,PluginCheck.Security.DirectDB.UnescapedDBParameter
	// Custom table operation, SQL is prepared with $wpdb->prepare()
	$prepared_sql = $wpdb->prepare( $sql_template, $current_time_str, $current_time_str );
	$results = $wpdb->get_results( $prepared_sql );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL,PluginCheck.Security.DirectDB.UnescapedDBParameter
	// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	
	return $results;
	}

	/**
	 * Check if a banner is currently active based on scheduling.
	 *
	 * @since 2.0.0
	 * @param object $banner Banner object.
	 * @return bool True if banner should be displayed, false otherwise.
	 */
	public function is_banner_active_by_schedule( $banner ) {
		if ( ! $banner->is_active ) {
			return false;
		}

		// Get WordPress timezone
		$timezone_string = \get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			try {
				$wp_timezone = new \DateTimeZone( $timezone_string );
			} catch ( \Exception $e ) {
				$wp_timezone = new \DateTimeZone( 'UTC' );
			}
		} else {
			$wp_timezone = new \DateTimeZone( 'UTC' );
		}

		$current_time = new \DateTime( 'now', $wp_timezone );

		// Check start date
		if ( ! empty( $banner->start_date ) ) {
			try {
				$start_date = new \DateTime( $banner->start_date, $wp_timezone );
				if ( $current_time < $start_date ) {
					return false;
				}
			} catch ( \Exception $e ) {
				// If we can't parse start date, assume it's valid
			}
		}

		// Check end date
		if ( ! empty( $banner->end_date ) ) {
			try {
				$end_date = new \DateTime( $banner->end_date, $wp_timezone );
				if ( $current_time > $end_date ) {
					return false;
				}
			} catch ( \Exception $e ) {
				// If we can't parse end date, assume it's valid
			}
		}

		return true;
	}
}