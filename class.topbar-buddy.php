<?php
/**
 * Main Plugin Class
 *
 * @package TopBar Buddy
 * @since 1.0.0
 */

namespace ElearningEvolve\TopBarBuddy;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = EEAB_VERSION;

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks and filters.
	 *
	 * @since 1.0.0
	 */
	private function init() {
		// Enqueue scripts with high priority to load after theme/Elementor
		\add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 999 );
		\add_action( 'wp_body_open', array( $this, 'wp_body_open_banner' ) );
		\add_action( 'wp_footer', array( $this, 'prevent_css_removal' ) );
		\add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		\add_action( 'admin_init', array( $this, 'register_settings' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		\add_action( 'add_option_topbar_buddy_clear_cache', array( $this, 'clear_all_caches' ), 10, 2 );
		\add_action( 'update_option_topbar_buddy_clear_cache', array( $this, 'clear_all_caches' ), 10, 3 );
	}

	/**
	 * Get WordPress timezone.
	 *
	 * @since 1.0.0
	 * @return \DateTimeZone WordPress timezone object.
	 */
	private function get_wp_timezone() {
		$timezone_string = \get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			return new \DateTimeZone( $timezone_string );
		}

		// Fallback to UTC offset if timezone string is not set.
		$offset = \get_option( 'gmt_offset' );
		$hours  = (int) $offset;
		$minutes = abs( ( $offset - (int) $offset ) * 60 );
		$sign    = $offset >= 0 ? '+' : '-';
		$tz      = sprintf( 'UTC%s%02d:%02d', $sign, abs( $hours ), $minutes );
		return new \DateTimeZone( $tz );
	}

	/**
	 * Get WordPress timezone string for display.
	 *
	 * @since 1.0.0
	 * @return string Timezone string for display.
	 */
	public function get_wp_timezone_string() {
		$timezone_string = \get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			return $timezone_string;
		}

		$offset = \get_option( 'gmt_offset' );
		$hours  = (int) $offset;
		$minutes = abs( ( $offset - (int) $offset ) * 60 );
		$sign    = $offset >= 0 ? '+' : '-';
		return sprintf( 'UTC%s%02d:%02d', $sign, abs( $hours ), $minutes );
	}

	/**
	 * Check if banner should be removed before start date.
	 *
	 * @since 1.0.0
	 * @param string $banner_id Banner ID suffix.
	 * @return bool True if banner should be hidden, false otherwise.
	 */
	public function is_removed_before_date( $banner_id ) {
		$start_after_date = \get_option( 'topbar_buddy_start_after_date' . $banner_id );

		if ( empty( $start_after_date ) ) {
			return false; // No start date set, so don't hide
		}

		$wp_timezone = $this->get_wp_timezone();
		$curr_date   = new \DateTime( 'now', $wp_timezone );

		try {
			// Parse the date string - can be in various formats
			// Create DateTime from the string, treating it as being in WordPress timezone
			$start_date = false;
			$date_string = trim( $start_after_date );
			
			// Try ISO format with T separator first (what's actually being saved: "2025-11-21T05:49")
			$start_date = \DateTime::createFromFormat( 'Y-m-d\TH:i', $date_string, $wp_timezone );
			
			// If that fails, try with seconds
			if ( $start_date === false ) {
				$start_date = \DateTime::createFromFormat( 'Y-m-d\TH:i:s', $date_string, $wp_timezone );
			}
			
			// Try format "Y-m-d H:i:s" (what JavaScript was supposed to save)
			if ( $start_date === false ) {
				$start_date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $date_string, $wp_timezone );
			}
			
			// If that format fails, try without seconds
			if ( $start_date === false ) {
				$start_date = \DateTime::createFromFormat( 'Y-m-d H:i', $date_string, $wp_timezone );
			}
			
			// If that also fails, try default parsing (less reliable but fallback)
			if ( $start_date === false ) {
				try {
					$start_date = new \DateTime( $date_string, $wp_timezone );
				} catch ( \Exception $e ) {
					$start_date = false;
				}
			}
			
			// If we still couldn't parse it, don't hide the banner (fail open)
			if ( $start_date === false ) {
				return false;
			}
			
			// Compare: hide banner if current time is BEFORE start time
			// So if current is 5:48 and start is 5:49: 5:48 < 5:49 is true, so return true (hide)
			// If current is 5:49 and start is 5:49: 5:49 < 5:49 is false, so return false (don't hide) - SHOW
			// If current is 5:50 and start is 5:49: 5:50 < 5:49 is false, so return false (don't hide) - SHOW
			$result = $curr_date < $start_date;
			return $result;
		} catch ( \Exception $e ) {
			// On any error, don't hide the banner (fail open)
			return false;
		}
	}

	/**
	 * Check if banner should be removed after end date.
	 *
	 * @since 1.0.0
	 * @param string $banner_id Banner ID suffix.
	 * @return bool True if banner should be hidden, false otherwise.
	 */
	public function is_removed_after_date( $banner_id ) {
		$remove_after_date = \get_option( 'topbar_buddy_remove_after_date' . $banner_id );

		if ( empty( $remove_after_date ) ) {
			return false; // No end date set, so don't hide
		}

		$wp_timezone = $this->get_wp_timezone();
		$curr_date   = new \DateTime( 'now', $wp_timezone );

		try {
			// Parse the date string - can be in various formats
			// Create DateTime from the string, treating it as being in WordPress timezone
			$end_date = false;
			$date_string = trim( $remove_after_date );
			
			// Try ISO format with T separator first (what's actually being saved: "2025-11-21T06:55")
			$end_date = \DateTime::createFromFormat( 'Y-m-d\TH:i', $date_string, $wp_timezone );
			
			// If that fails, try with seconds
			if ( $end_date === false ) {
				$end_date = \DateTime::createFromFormat( 'Y-m-d\TH:i:s', $date_string, $wp_timezone );
			}
			
			// Try format "Y-m-d H:i:s" (what JavaScript was supposed to save)
			if ( $end_date === false ) {
				$end_date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $date_string, $wp_timezone );
			}
			
			// If that format fails, try without seconds
			if ( $end_date === false ) {
				$end_date = \DateTime::createFromFormat( 'Y-m-d H:i', $date_string, $wp_timezone );
			}
			
			// If that also fails, try default parsing (less reliable but fallback)
			if ( $end_date === false ) {
				try {
					$end_date = new \DateTime( $date_string, $wp_timezone );
				} catch ( \Exception $e ) {
					$end_date = false;
				}
			}
			
			// If we still couldn't parse it, don't hide the banner (fail open)
			if ( $end_date === false ) {
				return false;
			}
			
			// Compare: hide banner if current time is AFTER end time (not equal to)
			// So if current is 6:54 and end is 6:55: 6:54 > 6:55 is false, so return false (don't hide) - SHOW
			// If current is 6:55 and end is 6:55: 6:55 > 6:55 is false, so return false (don't hide) - SHOW
			// If current is 6:56 and end is 6:55: 6:56 > 6:55 is true, so return true (hide) - HIDE
			$result = $curr_date > $end_date;
			return $result;
		} catch ( \Exception $e ) {
			// On any error, don't hide the banner (fail open)
			return false;
		}
	}

	/**
	 * Get disabled pages array.
	 *
	 * @since 1.0.0
	 * @param string $banner_id Banner ID suffix.
	 * @return array Array of disabled page IDs.
	 */
	private function get_disabled_pages_array( $banner_id ) {
		$disabled = \get_option( 'eeab_disabled_pages_array' . $banner_id );
		return array_filter( explode( ',', $disabled ) );
	}

	/**
	 * Check if current page is a post.
	 *
	 * @since 1.0.0
	 * @return bool True if current page is a post, false otherwise.
	 */
	private function is_current_page_a_post() {
		if ( ! \is_singular() ) {
			return false;
		}
		
		$post_type = \get_post_type();
		return 'post' === $post_type;
	}

	/**
	 * Check if banner is disabled on posts.
	 *
	 * @since 1.0.0
	 * @param string $banner_id Banner ID suffix.
	 * @return bool True if disabled on posts, false otherwise.
	 */
	private function is_disabled_on_posts( $banner_id ) {
		return \get_option( 'eeab_disabled_on_posts' . $banner_id );
	}

	/**
	 * Get current page/post ID.
	 *
	 * @since 1.0.0
	 * @return int|false Current page/post ID or false if not found.
	 */
	private function get_current_page_id() {
		// Try get_queried_object_id() first (works for pages, posts, archives, etc.)
		$page_id = \get_queried_object_id();
		
		if ( $page_id ) {
			return $page_id;
		}

		// Fallback to get_the_ID() for singular pages/posts.
		if ( \is_singular() ) {
			$page_id = \get_the_ID();
			if ( $page_id ) {
				return $page_id;
			}
		}

		// Check if it's the homepage (static page).
		if ( \is_front_page() && \is_page() ) {
			$page_id = \get_option( 'page_on_front' );
			if ( $page_id ) {
				return (int) $page_id;
			}
		}

		// Check if it's the blog page.
		if ( \is_home() && ! \is_front_page() ) {
			$page_id = \get_option( 'page_for_posts' );
			if ( $page_id ) {
				return (int) $page_id;
			}
		}

		return false;
	}

	/**
	 * Check if banner is disabled on current page.
	 *
	 * @since 1.0.0
	 * @param string $banner_id Banner ID suffix.
	 * @return bool True if disabled, false otherwise.
	 */
	private function is_disabled_on_current_page( $banner_id ) {
		$disabled_pages = $this->get_disabled_pages_array( $banner_id );
		$current_id     = $this->get_current_page_id();

		$removed_before = $this->is_removed_before_date( $banner_id );
		$removed_after = $this->is_removed_after_date( $banner_id );

		// Check if disabled on posts.
		$disabled_on_posts = $this->is_disabled_on_posts( $banner_id ) && $this->is_current_page_a_post();

		// Check if disabled on specific page IDs.
		// Convert both to strings for comparison since disabled_pages_array stores strings.
		$disabled_on_page = false;
		if ( ! empty( $disabled_pages ) && $current_id !== false ) {
			$current_id_str = (string) $current_id;
			$disabled_pages_str = array_map( 'strval', $disabled_pages );
			$disabled_on_page = in_array( $current_id_str, $disabled_pages_str, true );
		}

		// Check if disabled on specific URL paths.
		$disabled_on_path = $this->is_disabled_on_current_path( $banner_id );

		$disabled = (
			$disabled_on_page
			|| $disabled_on_posts
			|| $disabled_on_path
			|| $removed_before
			|| $removed_after
		);

		return $disabled;
	}

	/**
	 * Check if banner is disabled on current URL path.
	 *
	 * @since 1.0.0
	 * @param string $banner_id Banner ID suffix.
	 * @return bool True if disabled on current path, false otherwise.
	 */
	private function is_disabled_on_current_path( $banner_id ) {
		$disabled_paths = \get_option( 'topbar_buddy_disabled_page_paths' . $banner_id );
		
		if ( empty( $disabled_paths ) ) {
			return false;
		}

		// Get current request URI and sanitize it.
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_uri = \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$parsed_url = \wp_parse_url( $request_uri, PHP_URL_PATH );
		
		if ( empty( $parsed_url ) ) {
			$parsed_url = $request_uri;
		}

		// Remove query string and get just the path.
		$current_path = trim( $parsed_url, '/' );

		// Split paths by comma and check each one.
		$paths = array_map( 'trim', explode( ',', $disabled_paths ) );
		
		foreach ( $paths as $path ) {
			if ( empty( $path ) ) {
				continue;
			}

			// Remove leading/trailing slashes for comparison.
			$path = trim( $path, '/' );

			// Exact match.
			if ( $current_path === $path ) {
				return true;
			}

			// Check if current path starts with the disabled path (for sub-paths).
			if ( ! empty( $path ) && strpos( $current_path . '/', $path . '/' ) === 0 ) {
				return true;
			}

			// Check if path contains wildcards.
			if ( strpos( $path, '*' ) !== false ) {
				// Convert wildcard pattern to regex.
				$pattern = str_replace( '*', '.*', preg_quote( $path, '/' ) );
				if ( preg_match( '/^' . $pattern . '$/', $current_path ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Enqueue styles with high priority to load after theme/Elementor styles.
		\wp_register_style(
			'eeab-style',
			EEAB_PLUGIN_DIR_URL . 'topbar-buddy.css',
			array(), // No dependencies - we want it to load late
			EEAB_VERSION
		);
		\wp_enqueue_style( 'eeab-style' );
		
		// Generate and add inline styles using standard WordPress approach
		$inline_css = $this->generate_inline_styles();
		if ( ! empty( $inline_css ) ) {
			// Escape CSS before adding as inline style
		\wp_add_inline_style( 'eeab-style', \wp_strip_all_tags( $inline_css ) );
		}

		// Prepare script parameters.
		$wp_timezone = $this->get_wp_timezone();
		$banner_params = array();

		for ( $i = 1; $i <= $this->get_num_banners(); $i++ ) {
			$banner_id = $this->get_banner_id( $i );
			$disabled_on_current_page = $this->is_disabled_on_current_page( $banner_id );
			$eeab_hide_banner = \get_option( 'eeab_hide_banner' . $banner_id );
			
			// Get banner text - set to empty if hidden or disabled (matching original plugin logic)
			$banner_text = \get_option( 'topbar_buddy_text' . $banner_id );
			
			// If banner is hidden OR disabled on current page, set text to empty (original plugin sets it twice, second overrides)
			if ( $eeab_hide_banner === 'yes' || $disabled_on_current_page ) {
				$banner_text = '';
			}
			
			$banner_params[] = array(
				'hide_topbar_buddy'                => $eeab_hide_banner,
				'topbar_buddy_prepend_element'     => \get_option( 'topbar_buddy_prepend_element' . $banner_id ),
				'topbar_buddy_position'            => \get_option( 'topbar_buddy_position' . $banner_id ),
				'eeab_header_margin'                     => $i === 1 ? \get_option( 'eeab_header_margin' . $banner_id ) : '',
				'eeab_header_padding'                    => $i === 1 ? \get_option( 'eeab_header_padding' . $banner_id ) : '',
				'wp_body_open_enabled'             => $i === 1 ? \get_option( 'eeab_wp_body_open_enabled' . $banner_id ) : '',
				'wp_body_open'                     => function_exists( 'wp_body_open' ),
				'topbar_buddy_z_index'            => \get_option( 'topbar_buddy_z_index' . $banner_id ),
				'topbar_buddy_text'                => $banner_text,
				'disabled_on_current_page'         => $disabled_on_current_page,
				'disabled_pages_array'             => $this->get_disabled_pages_array( $banner_id ),
				'is_current_page_a_post'           => $this->is_current_page_a_post(),
				'disabled_on_posts'                => $this->is_disabled_on_posts( $banner_id ),
				'topbar_buddy_disabled_page_paths' => \get_option( 'topbar_buddy_disabled_page_paths' . $banner_id ),
				'topbar_buddy_font_size'          => \get_option( 'topbar_buddy_font_size' . $banner_id ),
				'topbar_buddy_color'              => \get_option( 'topbar_buddy_color' . $banner_id ),
				'topbar_buddy_text_color'         => \get_option( 'topbar_buddy_text_color' . $banner_id ),
				'topbar_buddy_link_color'         => \get_option( 'topbar_buddy_link_color' . $banner_id ),
				'topbar_buddy_close_color'        => \get_option( 'topbar_buddy_close_color' . $banner_id ),
				'topbar_buddy_custom_css'         => \get_option( 'topbar_buddy_custom_css' . $banner_id ),
				'topbar_buddy_scrolling_custom_css' => \get_option( 'topbar_buddy_scrolling_custom_css' . $banner_id ),
				'topbar_buddy_text_custom_css'    => \get_option( 'topbar_buddy_text_custom_css' . $banner_id ),
				'topbar_buddy_button_css'          => \get_option( 'topbar_buddy_button_css' . $banner_id ),
				'eeab_close_button_enabled'             => \get_option( 'eeab_close_button_enabled' . $banner_id ),
				'eeab_close_button_expiration'         => \get_option( 'eeab_close_button_expiration' . $banner_id ),
				'close_button_cookie_set'          => isset( $_COOKIE[ 'topbarbuddyclosed' . $banner_id ] ),
				'topbar_buddy_insert_inside_element' => \get_option( 'topbar_buddy_insert_inside_element' . $banner_id ),
				'wp_timezone'                      => $this->get_wp_timezone_string(),
			);
		}

		$script_params = array(
			'pro_version_enabled' => false, // Always false - no Pro version in Reloaded.
			'debug_mode'          => \get_option( 'topbar_buddy_debug_mode' ),
			'id'                  => \get_the_ID(),
			'version'             => EEAB_VERSION,
			'banner_params'       => $banner_params,
		);

		// Register script if not already registered.
		if ( ! \wp_script_is( 'eeab-script', 'registered' ) ) {
			\wp_register_script(
				'eeab-script',
				EEAB_PLUGIN_DIR_URL . 'topbar-buddy.js',
				array( 'jquery' ),
				EEAB_VERSION,
				true
			);
		}
		
		// Always update inline script params (don't use || to prevent stale cache issues).
		\wp_add_inline_script(
			'eeab-script',
			'window.topbarBuddyScriptParams = ' . \wp_json_encode( $script_params ) . ';',
			'before'
		);
		
		// Enqueue script.
		\wp_enqueue_script( 'eeab-script' );
	}

	/**
	 * Output banner using wp_body_open hook.
	 *
	 * @since 1.0.0
	 */
	public function wp_body_open_banner() {
		if ( ! function_exists( 'wp_body_open' ) ) {
			return;
		}

		$banner_id = $this->get_banner_id( 1 );
		$disabled_on_current_page = $this->is_disabled_on_current_page( $banner_id );
		$close_button_enabled = ! empty( \get_option( 'eeab_close_button_enabled' . $banner_id ) );
		$closed_cookie = $close_button_enabled && isset( $_COOKIE[ 'simplebannerclosed' . $banner_id ] );
		$banner_text = \get_option( 'topbar_buddy_text' . $banner_id );
		$eeab_hide_banner = \get_option( 'eeab_hide_banner' . $banner_id );

		// Match original plugin: only check disabled_on_current_page and cookie, not eeab_hide_banner
		// (eeab_hide_banner is handled by setting text to empty in enqueue_scripts)
		if ( ! $disabled_on_current_page && ! $closed_cookie && ! empty( $banner_text ) && $eeab_hide_banner !== 'yes' ) {
			$close_button = $close_button_enabled ? '<button id="topbar-buddy-close-button' . \esc_attr( $banner_id ) . '" class="topbar-buddy-button' . \esc_attr( $banner_id ) . '">&#x2715;</button>' : '';
			// Add data attribute so JS knows this was created by PHP
			// Use wp_kses_post to allow rich HTML content (already sanitized on save)
			echo '<div id="topbar-buddy' . \esc_attr( $banner_id ) . '" class="topbar-buddy' . \esc_attr( $banner_id ) . '" data-created-by="php"><div class="topbar-buddy-text' . \esc_attr( $banner_id ) . '"><span>' 
				. \wp_kses_post( $banner_text ) 
				. '</span>' 
				. wp_kses( $close_button, array( 'button' => array( 'id' => array(), 'class' => array() ) ) ) 
				. '</div></div>';
			// Add script to mark banner as loaded immediately to prevent flash and scroll into view if needed
			$inline_script = '(function() {
				document.body.classList.add("topbar-buddy-loaded");
				// Scroll banner into view if user is scrolled down
				// Check if banner is not visible in viewport
				const banner = document.getElementById("topbar-buddy");
				if (banner && window.scrollY > 50) {
					const rect = banner.getBoundingClientRect();
					const isVisible = rect.top >= 0 && rect.top < window.innerHeight;
					
					// If banner is not visible, scroll to it
					if (!isVisible) {
						if ("scrollBehavior" in document.documentElement.style) {
							banner.scrollIntoView({ behavior: "smooth", block: "start" });
						} else {
							// Fallback for older browsers
							window.scrollTo(0, 0);
						}
					}
				}
			})();';
			\wp_add_inline_script( 'eeab-script', $inline_script );
		}
	}

	/**
	 * Prevent CSS removal from optimizer plugins.
	 *
	 * @since 1.0.0
	 */
	public function prevent_css_removal() {
		echo '<div class="topbar-buddy topbar-buddy-text" style="display:none !important"></div>';
	}

	/**
	 * Generate inline styles for banners.
	 *
	 * @since 1.0.0
	 * @return string Combined CSS string.
	 */
	private function generate_inline_styles() {
		$inline_css = '';

		for ( $i = 1; $i <= $this->get_num_banners(); $i++ ) {
			$banner_id = $this->get_banner_id( $i );
			$closed_cookie = \get_option( 'eeab_close_button_enabled' . $banner_id ) && isset( $_COOKIE[ 'simplebannerclosed' . $banner_id ] );
			$disabled_on_current_page = $this->is_disabled_on_current_page( $banner_id );
			$banner_is_disabled = $disabled_on_current_page || \get_option( 'eeab_hide_banner' . $banner_id ) === 'yes';

			if ( $banner_is_disabled || $closed_cookie ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '{display:none;}';
			}

			if ( $i === 1 && ! $banner_is_disabled && ! $closed_cookie && \get_option( 'eeab_header_margin' . $banner_id ) !== '' ) {
				$inline_css .= 'header{margin-top:' . \esc_attr( \get_option( 'eeab_header_margin' . $banner_id ) ) . ';}';
			}

			if ( $i === 1 && ! $banner_is_disabled && ! $closed_cookie && \get_option( 'eeab_header_padding' . $banner_id ) !== '' ) {
				$inline_css .= 'header{padding-top:' . \esc_attr( \get_option( 'eeab_header_padding' . $banner_id ) ) . ';}';
			}

			$position = \get_option( 'topbar_buddy_position' . $banner_id );
			if ( $position !== '' ) {
				if ( $position === 'footer' ) {
					$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '{position:fixed;bottom:0;}';
				} else {
					$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '{position:' . \esc_attr( $position ) . ';}';
				}
			}

			$font_size = \get_option( 'topbar_buddy_font_size' . $banner_id );
			if ( $font_size !== '' ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . ' .topbar-buddy-text' . \esc_attr( $banner_id ) . '{font-size:' . \esc_attr( $font_size ) . ';}';
			}

			// Always output color styles - use custom if set, otherwise defaults
			$bg_color = \get_option( 'topbar_buddy_color' . $banner_id, '' );
			$text_color = \get_option( 'topbar_buddy_text_color' . $banner_id, '' );
			$link_color = \get_option( 'topbar_buddy_link_color' . $banner_id, '' );
			
			// Use custom colors if set, otherwise use defaults (black background, white text)
			$final_bg_color = ( $bg_color !== '' && $bg_color !== false ) ? $bg_color : '#000000';
			$final_text_color = ( $text_color !== '' && $text_color !== false ) ? $text_color : '#ffffff';
			$final_link_color = ( $link_color !== '' && $link_color !== false ) ? $link_color : '#f16521';
			
			// Always output color styles with body selector for higher specificity
			$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '{background-color:' . \esc_attr( $final_bg_color ) . ';}';
			$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . ' .topbar-buddy-text' . \esc_attr( $banner_id ) . '{color:' . \esc_attr( $final_text_color ) . ';}';
			$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . ' .topbar-buddy-text' . \esc_attr( $banner_id ) . ' a{color:' . \esc_attr( $final_link_color ) . ';}';

			$z_index = \get_option( 'topbar_buddy_z_index' . $banner_id );
			if ( $z_index !== '' ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '{z-index:' . \esc_attr( $z_index ) . ';}';
			} else {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '{z-index:99999;}';
			}

			$close_color = \get_option( 'topbar_buddy_close_color' . $banner_id );
			if ( $close_color !== '' ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . ' .topbar-buddy-button' . \esc_attr( $banner_id ) . '{color:' . \esc_attr( $close_color ) . ';}';
			}

			$custom_css = \get_option( 'topbar_buddy_custom_css' . $banner_id );
			if ( $custom_css !== '' ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '{' . \esc_html( $custom_css ) . '}';
			}

			$scrolling_css = \get_option( 'topbar_buddy_scrolling_custom_css' . $banner_id );
			if ( $scrolling_css !== '' ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . '.topbar-buddy-scrolling' . \esc_attr( $banner_id ) . '{' . \esc_html( $scrolling_css ) . '}';
			}

			$text_custom_css = \get_option( 'topbar_buddy_text_custom_css' . $banner_id );
			if ( $text_custom_css !== '' ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . ' .topbar-buddy-text' . \esc_attr( $banner_id ) . '{' . \esc_html( $text_custom_css ) . '}';
			}

			$button_css = \get_option( 'topbar_buddy_button_css' . $banner_id );
			if ( $button_css !== '' ) {
				$inline_css .= 'body .topbar-buddy' . \esc_attr( $banner_id ) . ' .topbar-buddy-button' . \esc_attr( $banner_id ) . '{' . \esc_html( $button_css ) . '}';
			}
		}

		return $inline_css;
	}

	/**
	 * Get number of banners (always 1 for free version).
	 *
	 * @since 1.0.0
	 * @return int Number of banners.
	 */
	private function get_num_banners() {
		return 1;
	}

	/**
	 * Get banner ID suffix.
	 *
	 * @since 1.0.0
	 * @param int $i Banner number.
	 * @return string Banner ID suffix.
	 */
	private function get_banner_id( $i ) {
		return $i === 1 ? '' : '_' . $i;
	}

	/**
	 * Add admin menu.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		\add_menu_page(
			\__( 'TopBar Buddy Settings', 'topbar-buddy' ),
			\__( 'TopBar Buddy', 'topbar-buddy' ),
			'manage_options',
			'topbar-buddy-settings',
			array( $this, 'settings_page' ),
			'dashicons-megaphone'
		);
	}
	
	/**
	 * Display banner management page.
	 *
	 * @since 2.0.0
	 */
	public function banner_management_page() {
		require_once EEAB_PLUGIN_DIR_PATH . 'admin/banner-management.php';
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Load on our settings page and banner management page.
		if ( 'toplevel_page_topbar-buddy-settings' !== $hook && 'topbar-buddy_page_topbar-buddy-banners' !== $hook ) {
			return;
		}
		
		// Enqueue styles for banner management page.
		if ( 'topbar-buddy_page_topbar-buddy-banners' === $hook ) {
			\wp_enqueue_style(
				'eeab-banner-management',
				EEAB_PLUGIN_DIR_URL . 'admin/styles/banner-management.css',
				array(),
				EEAB_VERSION
			);
			
			// Add inline styles for banner management page.
			$inline_css = '.status-active { color: #28a745; font-weight: bold; }
.status-scheduled { color: #007cba; font-weight: bold; }
.status-inactive { color: #dc3545; font-weight: bold; }
.sb-banner-form .form-table th { width: 200px; }';
			\wp_add_inline_style( 'eeab-banner-management', $inline_css );
		}

		// Enqueue admin styles.
		\wp_enqueue_style(
			'eeab-admin-main',
			EEAB_PLUGIN_DIR_URL . 'admin/styles/main.css',
			array(),
			EEAB_VERSION
		);

		\wp_enqueue_style(
			'eeab-admin-preview',
			EEAB_PLUGIN_DIR_URL . 'admin/styles/preview-banner.css',
			array(),
			EEAB_VERSION
		);

		// Enqueue frontend banner CSS for preview styling.
		\wp_enqueue_style(
			'eeab-preview',
			EEAB_PLUGIN_DIR_URL . 'topbar-buddy.css',
			array(),
			EEAB_VERSION
		);

		// Load DOMPurify if available.
		$purify_path = EEAB_PLUGIN_DIR_PATH . 'vendors/purify.min.js';
		if ( file_exists( $purify_path ) ) {
			\wp_enqueue_script(
				'eeab-dompurify',
				EEAB_PLUGIN_DIR_URL . 'vendors/purify.min.js',
				array(),
				EEAB_VERSION,
				true
			);
		}

		// Enqueue settings page script.
		\wp_enqueue_script(
			'eeab-settings-script',
			EEAB_PLUGIN_DIR_URL . 'admin/settings-script.js',
			array( 'jquery' ),
			EEAB_VERSION,
			true
		);

		// Localize script with banner ID.
		\wp_localize_script(
			'eeab-settings-script',
			'eeabSettings',
			array(
				'banner_id' => '',
			)
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		\register_setting(
			'eeab_settings_group',
			'topbar_buddy_debug_mode',
			array(
				'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
			)
		);

		\register_setting(
			'eeab_settings_group',
			'topbar_buddy_clear_cache',
			array(
				'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
			)
		);

		\register_setting(
			'eeab_settings_group',
			'eeab_header_margin',
			array(
				'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
			)
		);

		\register_setting(
			'eeab_settings_group',
			'eeab_header_padding',
			array(
				'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
			)
		);

		\register_setting(
			'eeab_settings_group',
			'eeab_wp_body_open_enabled',
			array(
				'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
			)
		);

		for ( $i = 1; $i <= $this->get_num_banners(); $i++ ) {
			$banner_id = $this->get_banner_id( $i );

			\register_setting(
				'eeab_settings_group',
				'eeab_hide_banner' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_prepend_element' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_font_size' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_color' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_color' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_text_color' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_color' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_link_color' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_color' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_close_color' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_color' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_text' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_html_content' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_custom_css' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_css' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_scrolling_custom_css' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_css' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_text_custom_css' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_css' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_button_css' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_css' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_position' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_z_index' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'eeab_disabled_on_posts' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'eeab_disabled_pages_array' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'eeab_close_button_enabled' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'eeab_close_button_expiration' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			// Date scheduling - FREE FEATURE!
			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_start_after_date' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_remove_after_date' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_insert_inside_element' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_css' ),
				)
			);

			\register_setting(
				'eeab_settings_group',
				'topbar_buddy_disabled_page_paths' . $banner_id,
				array(
					'sanitize_callback' => array( $this, 'sanitize_nohtml' ),
				)
			);
		}
	}

	/**
	 * Clear all caches.
	 *
	 * @since 1.0.0
	 * @param mixed $old_value Old option value.
	 * @param mixed $value New option value.
	 * @param string $option Optional option name.
	 */
	public function clear_all_caches( $old_value, $value, $option = 'topbar_buddy_clear_cache' ) {
		try {
			$this->clear_w3_total_cache();
			$this->clear_wp_super_cache();
			$this->clear_wp_engine_cache();
			$this->clear_wp_fastest_cache();
			$this->clear_wp_rocket();
			$this->clear_auto_optimize_cache();
			$this->clear_litespeed_cache();
			$this->clear_hummingbird_cache();
			return true;
		} catch ( \Exception $e ) {
			return 1;
		}
	}

	/**
	 * Clear W3 Total Cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_w3_total_cache() {
		if ( function_exists( 'w3tc_flush_all' ) ) {
			\w3tc_flush_all();
		}
	}

	/**
	 * Clear WP Super Cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_wp_super_cache() {
		if ( function_exists( 'wp_cache_clean_cache' ) ) {
			// These are global variables from WP Super Cache plugin, not our plugin variables
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- External plugin global variables
			global $file_prefix, $supercachedir;
			if ( empty( $supercachedir ) && function_exists( 'get_supercache_dir' ) ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- External plugin global variable
				$supercachedir = \get_supercache_dir();
			}
			\wp_cache_clean_cache( $file_prefix );
		}
	}

	/**
	 * Clear WP Engine Cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_wp_engine_cache() {
		if ( ! class_exists( 'WpeCommon' ) ) {
			return;
		}
		// WP Engine cache clearing methods.
	}

	/**
	 * Clear WP Fastest Cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_wp_fastest_cache() {
		// This is a global variable from WP Fastest Cache plugin, not our plugin variable
		global $wp_fastest_cache;
		if ( method_exists( 'WpFastestCache', 'deleteCache' ) && ! empty( $wp_fastest_cache ) ) {
			$wp_fastest_cache->deleteCache();
		}
	}

	/**
	 * Clear WP Rocket.
	 *
	 * @since 1.0.0
	 */
	private function clear_wp_rocket() {
		if ( ! function_exists( 'rocket_clean_domain' ) ) {
			return;
		}
		\rocket_clean_domain();
		if ( function_exists( 'run_rocket_sitemap_preload' ) ) {
			\run_rocket_sitemap_preload();
		}
	}

	/**
	 * Clear Auto Optimize Cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_auto_optimize_cache() {
		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			\autoptimizeCache::clearall();
		}
	}

	/**
	 * Clear LiteSpeed Cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_litespeed_cache() {
		if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
			\LiteSpeed_Cache_API::purge_all();
		}
	}

	/**
	 * Clear Hummingbird Cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_hummingbird_cache() {
		if ( ! class_exists( '\Hummingbird\Core\Utils' ) ) {
			return;
		}
		$modules = \Hummingbird\Core\Utils::get_active_cache_modules();
		foreach ( $modules as $module => $name ) {
			$mod = \Hummingbird\Core\Utils::get_module( $module );
			if ( $mod->is_active() ) {
				if ( 'minify' === $module ) {
					$mod->clear_files();
				} else {
					$mod->clear_cache();
				}
			}
		}
	}

	/**
	 * Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		require_once EEAB_PLUGIN_DIR_PATH . 'admin/settings-page.php';
	}

	/**
	 * Sanitize color value (hex color).
	 *
	 * @since 1.0.0
	 * @param string|null $color Color value to sanitize.
	 * @return string Sanitized color value.
	 */
	public function sanitize_color( $color ) {
		// Handle null values
		if ( $color === null ) {
			return '';
		}
		
		// Remove any whitespace
		$color = trim( $color );
		
		// If empty, return empty string
		if ( empty( $color ) ) {
			return '';
		}
		
		// Check if it's a valid hex color (with or without #)
		if ( preg_match( '/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
			// Ensure it starts with #
			if ( strpos( $color, '#' ) !== 0 ) {
				$color = '#' . $color;
			}
			return $color;
		}
		
		// If not a valid hex color, try to sanitize as text (for CSS color names like "red", "blue", etc.)
		return \sanitize_text_field( $color );
	}

	/**
	 * Sanitize text field with null handling.
	 *
	 * @since 1.0.0
	 * @param string|null $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize_text_field( $value ) {
		if ( $value === null ) {
			return '';
		}
		return \sanitize_text_field( $value );
	}

	/**
	 * Sanitize HTML content with null handling.
	 *
	 * @since 1.0.0
	 * @param string|null $content Content to sanitize.
	 * @return string Sanitized content.
	 */
	public function sanitize_html_content( $content ) {
		if ( $content === null ) {
			return '';
		}
		return \wp_kses_post( $content );
	}

	/**
	 * Sanitize no HTML content with null handling.
	 *
	 * @since 1.0.0
	 * @param string|null $value Value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize_nohtml( $value ) {
		if ( $value === null ) {
			return '';
		}
		return \wp_filter_nohtml_kses( $value );
	}

	/**
	 * Sanitize CSS content with null handling.
	 *
	 * @since 1.0.0
	 * @param string|null $css CSS to sanitize.
	 * @return string Sanitized CSS.
	 */
	public function sanitize_css( $css ) {
		if ( $css === null ) {
			return '';
		}
		return \wp_strip_all_tags( $css );
	}

	/**
	 * Sanitize checkbox value.
	 *
	 * @since 1.0.0
	 * @param mixed $value Checkbox value.
	 * @return string '1' if checked, empty string if not.
	 */
	public function sanitize_checkbox( $value ) {
		return ! empty( $value ) ? '1' : '';
	}
}


