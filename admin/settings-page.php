<?php
/**
 * Settings Page
 *
 * @package TopBar Buddy
 * @since 1.0.0
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Local variables in this file are function-scoped, not global

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get WordPress timezone for display.
$topbar_buddy_timezone_string = \get_option( 'timezone_string' );
if ( ! empty( $topbar_buddy_timezone_string ) ) {
	$wp_timezone = $topbar_buddy_timezone_string;
} else {
	$topbar_buddy_offset = \get_option( 'gmt_offset' );
	$topbar_buddy_hours  = (int) $topbar_buddy_offset;
	$topbar_buddy_minutes = abs( ( $topbar_buddy_offset - (int) $topbar_buddy_offset ) * 60 );
	$topbar_buddy_sign    = $topbar_buddy_offset >= 0 ? '+' : '-';
	$wp_timezone = sprintf( 'UTC%s%02d:%02d', $topbar_buddy_sign, abs( $topbar_buddy_hours ), $topbar_buddy_minutes );
}

$banner_id = '';
$topbar_buddy_i = 1;
?>
<div class="wrap topbar-buddy-admin">
	<div class="sb-header">
		<h1><?php esc_html_e( 'TopBar Buddy Settings', 'topbar-buddy' ); ?></h1>
	</div>

	<!-- Banner Preview -->
	<div class="sb-previews">
		<div class="sb-preview-container-wrapper">
			<?php require EEAB_PLUGIN_DIR_PATH . 'admin/preview-banner.php'; ?>
		</div>
		<p class="sb-note"><em><?php esc_html_e( 'Note: Styles may vary based on your theme\'s CSS.', 'topbar-buddy' ); ?></em></p>
	</div>

	<!-- Settings Form -->
	<form class="sb-settings-form" method="post" action="<?php echo esc_url( \admin_url( 'options.php' ) ); ?>">
		<?php \settings_fields( 'eeab_settings_group' ); ?>
		
		<!-- Top Save Changes Button -->
		<div class="sb-header-actions" style="margin-bottom: 20px;">
			<button type="submit" id="sb-top-save-button" class="button button-primary button-large">
				<?php esc_html_e( 'Save Changes', 'topbar-buddy' ); ?>
			</button>
		</div>

		<?php require EEAB_PLUGIN_DIR_PATH . 'admin/banner-settings.php'; ?>

		<!-- Mobile Alert -->
		<div class="sb-mobile-alert">
			<strong><?php esc_html_e( 'Mobile Testing Reminder:', 'topbar-buddy' ); ?></strong> <?php esc_html_e( 'Always test your banners on mobile devices as theme headers often change their CSS for mobile views.', 'topbar-buddy' ); ?>
		</div>

		<!-- Cache Clear Hidden Field -->
		<?php
		$topbar_buddy_cache_value = \get_option( 'topbar_buddy_clear_cache' ) ? '' : '1';
		echo '<input type="hidden" name="topbar_buddy_clear_cache" value="' . esc_attr( $topbar_buddy_cache_value ) . '" />';
		?>

		<!-- Save Changes Button -->
		<?php \submit_button(); ?>
	</form>
</div>


