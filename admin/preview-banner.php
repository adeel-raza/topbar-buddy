<?php
/**
 * Banner Preview Component
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


?>
<div id="preview_banner_outer_container<?php echo esc_attr( $banner_id ); ?>"
     class="sb-preview-container topbar-buddy-settings-section">
    <div class="sb-preview-header">
        <h4><?php esc_html_e( 'Banner Preview', 'topbar-buddy' ); ?></h4>
    </div>
    <div id="preview_banner_inner_container<?php echo esc_attr( $banner_id ); ?>" class="sb-preview-wrapper">
        <!-- PREVIEW FILE LOADED v2 -->
        <div id="preview_banner<?php echo esc_attr( $banner_id ); ?>" class="topbar-buddy<?php echo esc_attr( $banner_id ); ?> sb-preview-banner" data-created-by="php" data-preview="true">
            <div id="preview_banner_text<?php echo esc_attr( $banner_id ); ?>" class="topbar-buddy-text<?php echo esc_attr( $banner_id ); ?> sb-preview-text">
                <?php
                $topbar_buddy_saved_banner_text = \get_option( 'topbar_buddy_text' . $banner_id, '' );
                if ( ! empty( $topbar_buddy_saved_banner_text ) ) {
                    echo '<span>' . wp_kses_post( $topbar_buddy_saved_banner_text ) . '</span>';
                } else {
                    echo '<span>' . esc_html__( 'This is what your banner will look like with a', 'topbar-buddy' ) . ' <a href="/">' . esc_html__( 'link', 'topbar-buddy' ) . '</a>.</span>';
                }
                error_log( 'DEBUG: About to echo button' );
                echo '<!-- BUTTON START -->';
                echo '<button style="display:inline-block; color: white; font-size: 18px; background: red; padding: 5px 10px; margin-left: 10px;">✕ TEST</button>';
                echo '<!-- BUTTON END -->';
                ?>
            </div>
        </div>
    </div>
</div>


