<?php
/**
 * Banner Manager Class
 *
 * @package TopBar Buddy
 * @since 2.0.0
 */

namespace ElearningEvolve\TopBarBuddy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a valid DateTimeZone object from WordPress settings.
 *
 * @return \DateTimeZone
 */
function get_wp_timezone() {
	$timezone_string = get_option( 'timezone_string' );

	if ( $timezone_string ) {
		try {
			return new \DateTimeZone( $timezone_string );
		} catch ( \Exception $e ) {
			// Fall through to offset-based timezone.
		}
	}

	$gmt_offset = get_option( 'gmt_offset', 0 );
	$hours = floor( $gmt_offset );
	$minutes = abs( ( $gmt_offset - $hours ) * 60 );
	$offset_string = sprintf( '%+03d:%02d', $hours, $minutes );

	try {
		return new \DateTimeZone( $offset_string );
	} catch ( \Exception $e ) {
		return new \DateTimeZone( 'UTC' );
	}
}

/**
 * Banner Manager class for database operations.
 *
 * @since 2.0.0
 */
class BannerManager {

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'topbar_buddy_banners';
		$this->init();
	}

	/**
	 * Initialize hooks.
	 */
	private function init() {
		\add_action( 'init', array( $this, 'maybe_create_table' ) );
		\add_action( 'init', array( $this, 'maybe_migrate_existing_banner' ) );
	}

	/**
	 * Create database table if it doesn't exist.
	 */
	public function maybe_create_table() {
		global $wpdb;
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT table_name FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$this->table_name
			)
		);

		if ( $table_exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$table_name_safe = esc_sql( $this->table_name );

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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Migrate existing banner from options to database.
	 */
	public function maybe_migrate_existing_banner() {
		if ( \get_option( 'topbar_buddy_migrated_to_multi_banner' ) ) {
			return;
		}

		$existing_text = \get_option( 'topbar_buddy_text' );
		if ( empty( $existing_text ) ) {
			\update_option( 'topbar_buddy_migrated_to_multi_banner', true );
			return;
		}

		$banner_data = array(
			'name'                    => __( 'Migrated Banner', 'topbar-buddy' ),
			'content'                 => $existing_text,
			'background_color'        => \get_option( 'topbar_buddy_color', '#000000' ),
			'text_color'              => \get_option( 'topbar_buddy_text_color', '#ffffff' ),
			'link_color'              => \get_option( 'topbar_buddy_link_color', '#f16521' ),
			'close_color'             => \get_option( 'topbar_buddy_close_color', '#ffffff' ),
			'font_size'               => \get_option( 'topbar_buddy_font_size', '' ),
			'position'                => \get_option( 'topbar_buddy_position', 'fixed' ),
			'z_index'                 => \get_option( 'topbar_buddy_z_index', '999999' ),
			'start_date'              => \get_option( 'topbar_buddy_start_after_date' ),
			'end_date'                => \get_option( 'topbar_buddy_remove_after_date' ),
			'is_active'               => \get_option( 'eeab_hide_banner' ) !== 'yes' ? 1 : 0,
			'disabled_on_posts'       => \get_option( 'eeab_disabled_on_posts' ) ? 1 : 0,
			'disabled_pages'          => \get_option( 'eeab_disabled_pages_array', '' ),
			'disabled_paths'          => \get_option( 'topbar_buddy_disabled_page_paths', '' ),
			'close_button_enabled'    => \get_option( 'eeab_close_button_enabled' ) ? 1 : 0,
			'close_button_expiration' => \get_option( 'eeab_close_button_expiration', 24 ),
			'custom_css'              => \get_option( 'topbar_buddy_custom_css', '' ),
			'scrolling_custom_css'    => \get_option( 'topbar_buddy_scrolling_custom_css', '' ),
			'text_custom_css'         => \get_option( 'topbar_buddy_text_custom_css', '' ),
			'button_css'              => \get_option( 'topbar_buddy_button_css', '' ),
			'prepend_element'         => \get_option( 'topbar_buddy_prepend_element', '' ),
			'insert_inside_element'   => \get_option( 'topbar_buddy_insert_inside_element', '' ),
		);

		try {
			$banner_data['start_date'] = ! empty( $banner_data['start_date'] )
				? ( new \DateTime( $banner_data['start_date'] ) )->format( 'Y-m-d H:i:s' )
				: null;
		} catch ( \Exception $e ) {
			$banner_data['start_date'] = null;
		}

		try {
			$banner_data['end_date'] = ! empty( $banner_data['end_date'] )
				? ( new \DateTime( $banner_data['end_date'] ) )->format( 'Y-m-d H:i:s' )
				: null;
		} catch ( \Exception $e ) {
			$banner_data['end_date'] = null;
		}

		$this->create_banner( $banner_data );
		\update_option( 'topbar_buddy_migrated_to_multi_banner', true );
	}

	/**
	 * Create a new banner.
	 *
	 * @param array $data Banner data.
	 * @return int|false Insert ID on success, false on failure.
	 */
	public function create_banner( $data ) {
		global $wpdb;

		$defaults = array(
			'name'                    => '',
			'content'                 => '',
			'background_color'        => '#000000',
			'text_color'              => '#ffffff',
			'link_color'              => '#f16521',
			'close_color'             => '#ffffff',
			'font_size'               => '',
			'position'                => 'fixed',
			'z_index'                 => '999999',
			'start_date'              => null,
			'end_date'                => null,
			'is_active'               => 1,
			'disabled_on_posts'       => 0,
			'disabled_pages'          => '',
			'disabled_paths'          => '',
			'close_button_enabled'    => 0,
			'close_button_expiration' => 24,
			'custom_css'              => '',
			'scrolling_custom_css'    => '',
			'text_custom_css'         => '',
			'button_css'              => '',
			'prepend_element'         => '',
			'insert_inside_element'   => '',
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->table_name,
			$data,
			array_fill( 0, count( $data ), '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a banner.
	 *
	 * @param int   $banner_id Banner ID.
	 * @param array $data      Banner data.
	 * @return bool True on success, false on failure.
	 */
	public function update_banner( $banner_id, $data ) {
		global $wpdb;
		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $banner_id ),
			null,
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Delete a banner.
	 *
	 * @param int $banner_id Banner ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_banner( $banner_id ) {
		global $wpdb;
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $banner_id ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Get a single banner.
	 *
	 * @param int $banner_id Banner ID.
	 * @return object|null Banner object or null.
	 */
	public function get_banner( $banner_id ) {
		global $wpdb;
		$table_name_safe = esc_sql( $this->table_name );
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table_name_safe}` WHERE id = %d",
				absint( $banner_id )
			)
		);
	}

	/**
	 * Get banners with optional filters.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of banner objects.
	 */
	public function get_banners( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'active_only' => false,
			'orderby'     => 'created_date',
			'order'       => 'ASC',
			'limit'       => null,
			'offset'      => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$allowed_orderby = array( 'id', 'name', 'created_date', 'start_date', 'end_date' );
		$allowed_order   = array( 'ASC', 'DESC' );

		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_date';
		$order   = in_array( strtoupper( $args['order'] ), $allowed_order, true ) ? strtoupper( $args['order'] ) : 'ASC';

		$table_name_safe = esc_sql( $this->table_name );
		$where_clause    = $args['active_only'] ? ' WHERE is_active = %d' : '';
		$order_clause    = ' ORDER BY `' . esc_sql( $orderby ) . '` ' . esc_sql( $order );
		$limit_clause    = $args['limit'] ? ' LIMIT %d OFFSET %d' : '';

		if ( $args['limit'] ) {
			if ( $args['active_only'] ) {
				$sql = $wpdb->prepare(
					"SELECT * FROM `{$table_name_safe}`{$where_clause}{$order_clause}{$limit_clause}",
					1,
					absint( $args['limit'] ),
					absint( $args['offset'] )
				);
			} else {
				$sql = $wpdb->prepare(
					"SELECT * FROM `{$table_name_safe}`{$where_clause}{$order_clause}{$limit_clause}",
					absint( $args['limit'] ),
					absint( $args['offset'] )
				);
			}
		} else {
			if ( $args['active_only'] ) {
				$sql = $wpdb->prepare(
					"SELECT * FROM `{$table_name_safe}`{$where_clause}{$order_clause}",
					1
				);
			} else {
				$sql = "SELECT * FROM `{$table_name_safe}`{$where_clause}{$order_clause}";
			}
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get currently active banners based on schedule.
	 *
	 * @return array Array of active banner objects.
	 */
	public function get_current_active_banners() {
		global $wpdb;
		$wp_timezone      = \ElearningEvolve\TopBarBuddy\get_wp_timezone();
		$current_time     = new \DateTime( 'now', $wp_timezone );
		$current_time_str = $current_time->format( 'Y-m-d H:i:s' );

		$table_name_safe = esc_sql( $this->table_name );
		$sql_template    = "SELECT * FROM `{$table_name_safe}` 
			WHERE is_active = 1 
			AND (start_date IS NULL OR start_date <= %s)
			AND (end_date IS NULL OR end_date > %s)
			ORDER BY created_date ASC";

		$prepared_sql = $wpdb->prepare( $sql_template, $current_time_str, $current_time_str );
		return $wpdb->get_results( $prepared_sql );
	}

	/**
	 * Check if banner is active by schedule.
	 *
	 * @param object $banner Banner object.
	 * @return bool True if active, false otherwise.
	 */
	public function is_banner_active_by_schedule( $banner ) {
		if ( ! $banner->is_active ) {
			return false;
		}

		$wp_timezone  = \ElearningEvolve\TopBarBuddy\get_wp_timezone();
		$current_time = new \DateTime( 'now', $wp_timezone );

		if ( ! empty( $banner->start_date ) ) {
			try {
				$start_date = new \DateTime( $banner->start_date, $wp_timezone );
				if ( $current_time < $start_date ) {
					return false;
				}
			} catch ( \Exception $e ) {
				// Invalid date, continue.
			}
		}

		if ( ! empty( $banner->end_date ) ) {
			try {
				$end_date = new \DateTime( $banner->end_date, $wp_timezone );
				if ( $current_time > $end_date ) {
					return false;
				}
			} catch ( \Exception $e ) {
				// Invalid date, continue.
			}
		}

		return true;
	}
}
