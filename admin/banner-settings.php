<?php
/**
 * Banner Settings Form
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


// Helper function for checked state.
function topbar_buddy_is_checked( $option_value ) {
	return $option_value ? 'checked ' : '';
}

// Helper function for checked state (escaped).
function topbar_buddy_is_checked_escaped( $option_value ) {
	return $option_value ? esc_attr( 'checked ' ) : '';
}

$topbar_buddy_wp_timezone = isset( $wp_timezone ) ? $wp_timezone : ( \get_option( 'timezone_string' ) ?: 'UTC' );
$topbar_buddy_current_date = new \DateTime( 'now', new \DateTimeZone( $topbar_buddy_wp_timezone ) );
$topbar_buddy_date_format = 'Y-m-d H:i:s';
$topbar_buddy_example_date = $topbar_buddy_current_date->format( $topbar_buddy_date_format );
?>
<div id="free_section<?php echo esc_attr( $banner_id ); ?>" class="sb-settings-section topbar-buddy-settings-section">
    <div class="sb-section-header">
        <h3><?php esc_html_e( 'Banner Settings', 'topbar-buddy' ); ?></h3>
    </div>
    <div class="sb-section-content">
        <table class="form-table">
            <!-- Banner Text -->
            <tr>
                <th scope="row">
                    <label for="topbar_buddy_text<?php echo esc_attr( $banner_id ); ?>"><?php esc_html_e( 'Banner Text', 'topbar-buddy' ); ?></label>
                    <div class="sb-field-description"><?php esc_html_e( 'Enter the text to display in your banner. Leave blank to hide the banner.', 'topbar-buddy' ); ?></div>
                </th>
                <td>
                    <?php
                    $topbar_buddy_editor_id = 'topbar_buddy_text' . $banner_id;
                    $topbar_buddy_editor_content = \get_option( 'topbar_buddy_text' . $banner_id, '' );
                    $topbar_buddy_editor_settings = array(
                        'textarea_name' => 'topbar_buddy_text' . $banner_id,
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny' => false,
                        'quicktags' => true,
                        'tinymce' => array(
                            'toolbar1' => 'bold,italic,underline,strikethrough,|,link,unlink,|,bullist,numlist,|,undo,redo',
                            'toolbar2' => '',
                            'toolbar3' => '',
                            'toolbar4' => '',
                        ),
                    );
                    \wp_editor( $topbar_buddy_editor_content, $topbar_buddy_editor_id, $topbar_buddy_editor_settings );
                    ?>
                </td>
            </tr>

            <!-- Banner Visibility -->
            <tr>
                <th scope="row"><?php esc_html_e( 'Banner Visibility', 'topbar-buddy' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e( 'Banner Visibility', 'topbar-buddy' ); ?></legend>
                        <label>
                            <input type="radio" name="eeab_hide_banner<?php echo esc_attr( $banner_id ); ?>" value="no" 
                                   <?php echo esc_attr( topbar_buddy_is_checked( \get_option( 'eeab_hide_banner' . $banner_id ) === 'no' ) ); ?>>
                            <?php esc_html_e( 'Show Banner', 'topbar-buddy' ); ?>
                        </label>
                        <label>
                            <input type="radio" name="eeab_hide_banner<?php echo esc_attr( $banner_id ); ?>" value="yes" 
                                   <?php echo esc_attr( topbar_buddy_is_checked( \get_option( 'eeab_hide_banner' . $banner_id ) === 'yes' ) ); ?>>
                            <?php esc_html_e( 'Hide Banner', 'topbar-buddy' ); ?>
                        </label>
                    </fieldset>
                    <div class="sb-field-description"><?php esc_html_e( 'Choose whether to show or hide the banner on your website.', 'topbar-buddy' ); ?></div>
                </td>
            </tr>

            <!-- Schedule Banner (FREE FEATURE!) -->
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Schedule Banner', 'topbar-buddy' ); ?>
                    <div class="sb-field-description" style="color: #28a745; font-weight: 600;">
                        <?php esc_html_e( 'Free Feature!', 'topbar-buddy' ); ?>
                    </div>
                </th>
                <td>
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #28a745;">
                        <strong><?php esc_html_e( 'Site Timezone:', 'topbar-buddy' ); ?></strong> 
                        <code><?php echo esc_html( $topbar_buddy_wp_timezone ); ?></code>
                        <div class="sb-field-description" style="margin-top: 5px;">
                            <?php esc_html_e( 'All dates and times use your WordPress site timezone setting.', 'topbar-buddy' ); ?>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label for="topbar_buddy_start_after_date<?php echo esc_attr( $banner_id ); ?>">
                                <?php esc_html_e( 'Start Date & Time', 'topbar-buddy' ); ?>
                            </label>
                            <input type="datetime-local" 
                                   id="topbar_buddy_start_after_date<?php echo esc_attr( $banner_id ); ?>" 
                                   name="topbar_buddy_start_after_date<?php echo esc_attr( $banner_id ); ?>" 
                                   style="width: 100%; padding: 8px; margin-top: 5px;"
                                   value="<?php 
                                        $topbar_buddy_start_date = \get_option( 'topbar_buddy_start_after_date' . $banner_id );
                                        if ( $topbar_buddy_start_date ) {
                                            try {
                                                $topbar_buddy_dt = new \DateTime( $topbar_buddy_start_date, new \DateTimeZone( $topbar_buddy_wp_timezone ) );
                                                echo esc_attr( $topbar_buddy_dt->format( 'Y-m-d\TH:i' ) );
                                            } catch ( \Exception $e ) {
                                                echo '';
                                            }
                                        }
                                   ?>" />
                            <div class="sb-field-description" style="margin-top: 5px;">
                                <?php esc_html_e( 'Banner will appear after this date and time. Leave empty to show immediately.', 'topbar-buddy' ); ?>
                            </div>
                        </div>
                        
                        <div>
                            <label for="topbar_buddy_remove_after_date<?php echo esc_attr( $banner_id ); ?>">
                                <?php esc_html_e( 'End Date & Time', 'topbar-buddy' ); ?>
                            </label>
                            <input type="datetime-local" 
                                   id="topbar_buddy_remove_after_date<?php echo esc_attr( $banner_id ); ?>" 
                                   name="topbar_buddy_remove_after_date<?php echo esc_attr( $banner_id ); ?>" 
                                   style="width: 100%; padding: 8px; margin-top: 5px;"
                                   value="<?php 
                                        $topbar_buddy_end_date = \get_option( 'topbar_buddy_remove_after_date' . $banner_id );
                                        if ( $topbar_buddy_end_date ) {
                                            try {
                                                $topbar_buddy_dt = new \DateTime( $topbar_buddy_end_date, new \DateTimeZone( $topbar_buddy_wp_timezone ) );
                                                echo esc_attr( $topbar_buddy_dt->format( 'Y-m-d\TH:i' ) );
                                            } catch ( \Exception $e ) {
                                                echo '';
                                            }
                                        }
                                   ?>" />
                            <div class="sb-field-description" style="margin-top: 5px;">
                                <?php esc_html_e( 'Banner will automatically hide after this date and time. Leave empty to show indefinitely.', 'topbar-buddy' ); ?>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- Colors -->
            <tr>
                <th scope="row"><?php esc_html_e( 'Colors', 'topbar-buddy' ); ?></th>
                <td>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label><?php esc_html_e( 'Background Color', 'topbar-buddy' ); ?></label>
                            <div class="sb-color-input-group">
                                <input type="text" id="topbar_buddy_color<?php echo esc_attr( $banner_id ); ?>" 
                                       name="topbar_buddy_color<?php echo esc_attr( $banner_id ); ?>" 
                                       placeholder="#000000"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_color' . $banner_id ) ); ?>" />
                                <input type="color" id="topbar_buddy_color_show<?php echo esc_attr( $banner_id ); ?>" 
                                       class="sb-color-picker"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_color' . $banner_id ) ?: '#000000' ); ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label><?php esc_html_e( 'Text Color', 'topbar-buddy' ); ?></label>
                            <div class="sb-color-input-group">
                                <input type="text" id="topbar_buddy_text_color<?php echo esc_attr( $banner_id ); ?>" 
                                       name="topbar_buddy_text_color<?php echo esc_attr( $banner_id ); ?>" 
                                       placeholder="#ffffff"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_text_color' . $banner_id ) ); ?>" />
                                <input type="color" id="topbar_buddy_text_color_show<?php echo esc_attr( $banner_id ); ?>" 
                                       class="sb-color-picker"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_text_color' . $banner_id ) ?: '#ffffff' ); ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label><?php esc_html_e( 'Link Color', 'topbar-buddy' ); ?></label>
                            <div class="sb-color-input-group">
                                <input type="text" id="topbar_buddy_link_color<?php echo esc_attr( $banner_id ); ?>" 
                                       name="topbar_buddy_link_color<?php echo esc_attr( $banner_id ); ?>" 
                                       placeholder="#f16521"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_link_color' . $banner_id ) ); ?>" />
                                <input type="color" id="topbar_buddy_link_color_show<?php echo esc_attr( $banner_id ); ?>" 
                                       class="sb-color-picker"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_link_color' . $banner_id ) ?: '#f16521' ); ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label><?php esc_html_e( 'Close Button Color', 'topbar-buddy' ); ?></label>
                            <div class="sb-color-input-group">
                                <input type="text" id="topbar_buddy_close_color<?php echo esc_attr( $banner_id ); ?>" 
                                       name="topbar_buddy_close_color<?php echo esc_attr( $banner_id ); ?>" 
                                       placeholder="#000000"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_close_color' . $banner_id ) ); ?>" />
                                <input type="color" id="topbar_buddy_close_color_show<?php echo esc_attr( $banner_id ); ?>" 
                                       class="sb-color-picker"
                                       value="<?php echo esc_attr( \get_option( 'topbar_buddy_close_color' . $banner_id ) ?: '#000000' ); ?>">
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- Layout & Positioning -->
            <tr>
                <th scope="row"><?php esc_html_e( 'Layout & Positioning', 'topbar-buddy' ); ?></th>
                <td>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        <div>
                            <label for="topbar_buddy_font_size<?php echo esc_attr( $banner_id ); ?>"><?php esc_html_e( 'Font Size', 'topbar-buddy' ); ?></label>
                            <input type="text" id="topbar_buddy_font_size<?php echo esc_attr( $banner_id ); ?>" 
                                   name="topbar_buddy_font_size<?php echo esc_attr( $banner_id ); ?>" 
                                   placeholder="16px"
                                   value="<?php echo esc_attr( \get_option( 'topbar_buddy_font_size' . $banner_id ) ); ?>" />
                            <div class="sb-field-description"><?php esc_html_e( 'Examples: 16px, 1.2em, 14pt', 'topbar-buddy' ); ?></div>
                        </div>
                        
                        <div>
                            <label for="topbar_buddy_z_index<?php echo esc_attr( $banner_id ); ?>"><?php esc_html_e( 'Z-Index', 'topbar-buddy' ); ?></label>
                            <input type="number" id="topbar_buddy_z_index<?php echo esc_attr( $banner_id ); ?>" 
                                   name="topbar_buddy_z_index<?php echo esc_attr( $banner_id ); ?>" 
                                   placeholder="99999"
                                   value="<?php echo esc_attr( \get_option( 'topbar_buddy_z_index' . $banner_id ) ); ?>" />
                            <div class="sb-field-description"><?php esc_html_e( 'Higher numbers appear above other elements', 'topbar-buddy' ); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e( 'Banner Position', 'topbar-buddy' ); ?></label>
                        <fieldset>
                            <?php
                            $topbar_buddy_positions = array(
                                'relative' => __( 'Relative (default)', 'topbar-buddy' ),
                                'static' => __( 'Static', 'topbar-buddy' ),
                                'absolute' => __( 'Absolute', 'topbar-buddy' ),
                                'fixed' => __( 'Fixed (stays in place when scrolling)', 'topbar-buddy' ),
                                'sticky' => __( 'Sticky (sticks when scrolling)', 'topbar-buddy' ),
                                'footer' => __( 'Footer (fixed at bottom)', 'topbar-buddy' ),
                            );
                            
                            $topbar_buddy_current_position = \get_option( 'topbar_buddy_position' . $banner_id );
                            foreach ( $topbar_buddy_positions as $topbar_buddy_value => $topbar_buddy_label ) {
                                $topbar_buddy_checked = ( $topbar_buddy_current_position === $topbar_buddy_value ) ? 'checked' : '';
                                echo '<label>';
                                echo '<input type="radio" name="topbar_buddy_position' . esc_attr( $banner_id ) . '" value="' . esc_attr( $topbar_buddy_value ) . '" ' . esc_attr( $topbar_buddy_checked ) . '> ';
                                echo esc_html( $topbar_buddy_label );
                                echo '</label>';
                            }
                            ?>
                        </fieldset>
                    </div>
                </td>
            </tr>

            <!-- Close Button -->
            <tr>
                <th scope="row"><?php esc_html_e( 'Close Button', 'topbar-buddy' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="eeab_close_button_enabled<?php echo esc_attr( $banner_id ); ?>" 
                               name="eeab_close_button_enabled<?php echo esc_attr( $banner_id ); ?>" 
                               <?php echo esc_attr( topbar_buddy_is_checked( \get_option( 'eeab_close_button_enabled' . $banner_id ) ) ); ?>>
                        <?php esc_html_e( 'Enable close button (users can dismiss the banner)', 'topbar-buddy' ); ?>
                    </label>
                    <div class="sb-field-description"><?php esc_html_e( 'Uses strictly necessary cookies (GDPR compliant)', 'topbar-buddy' ); ?></div>
                    
                    <div style="margin-top: 15px;">
                        <label for="eeab_close_button_expiration<?php echo esc_attr( $banner_id ); ?>"><?php esc_html_e( 'Remember Dismissal For', 'topbar-buddy' ); ?></label>
                        <input type="text" id="eeab_close_button_expiration<?php echo esc_attr( $banner_id ); ?>" 
                               name="eeab_close_button_expiration<?php echo esc_attr( $banner_id ); ?>" 
                               placeholder="14"
                               style="width: 200px; margin-top: 5px;"
                               value="<?php echo esc_attr( \get_option( 'eeab_close_button_expiration' . $banner_id ) ); ?>" />
                        <div class="sb-field-description">
                            <?php esc_html_e( 'Enter number of days (e.g., 14) or leave empty for session-only. Users won\'t see the banner again for this duration.', 'topbar-buddy' ); ?>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- Placement -->
            <tr>
                <th scope="row"><?php esc_html_e( 'Banner Placement', 'topbar-buddy' ); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e( 'Banner Placement', 'topbar-buddy' ); ?></legend>
                        <label>
                            <input type="radio" name="topbar_buddy_prepend_element<?php echo esc_attr( $banner_id ); ?>" value="body" 
                                   <?php echo esc_attr( topbar_buddy_is_checked( \get_option( 'topbar_buddy_prepend_element' . $banner_id ) === 'body' ) ); ?>>
                            <?php esc_html_e( 'Top of page (recommended)', 'topbar-buddy' ); ?>
                        </label>
                        <label>
                            <input type="radio" name="topbar_buddy_prepend_element<?php echo esc_attr( $banner_id ); ?>" value="header" 
                                   <?php echo esc_attr( topbar_buddy_is_checked( \get_option( 'topbar_buddy_prepend_element' . $banner_id ) === 'header' ) ); ?>>
                            <?php esc_html_e( 'Inside header element', 'topbar-buddy' ); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <!-- Page Exclusions -->
            <tr>
                <th scope="row"><?php esc_html_e( 'Hide Banner On', 'topbar-buddy' ); ?></th>
                <td>
                    <div style="margin-bottom: 15px;">
                        <label>
                            <input type="checkbox" name="disabled_on_posts<?php echo esc_attr( $banner_id ); ?>" 
                                   <?php echo esc_attr( topbar_buddy_is_checked( \get_option( 'disabled_on_posts' . $banner_id ) ) ); ?>>
                            <?php esc_html_e( 'Hide on all blog posts', 'topbar-buddy' ); ?>
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="topbar_buddy_disabled_page_paths<?php echo esc_attr( $banner_id ); ?>"><?php esc_html_e( 'Hide on Specific URLs', 'topbar-buddy' ); ?></label>
                        <input type="text" id="topbar_buddy_disabled_page_paths<?php echo esc_attr( $banner_id ); ?>" 
                               name="topbar_buddy_disabled_page_paths<?php echo esc_attr( $banner_id ); ?>" 
                               placeholder="/shop, /cart, /checkout"
                               style="width: 100%; margin-top: 5px;"
                               value="<?php echo esc_attr( \get_option( 'topbar_buddy_disabled_page_paths' . $banner_id ) ); ?>" />
                        <div class="sb-field-description">
                            <?php esc_html_e( 'Enter page paths separated by commas. Example: /shop, /cart, /checkout', 'topbar-buddy' ); ?>
                        </div>
                    </div>
                    
                    <div>
                        <label><?php esc_html_e( 'Hide on Specific Pages', 'topbar-buddy' ); ?></label>
                        <div id="topbar_buddy_pro_disabled_pages<?php echo esc_attr( $banner_id ); ?>" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px; background: #f9f9f9; border-radius: 4px;">
                            <?php
                            $disabled_pages_array = array_filter( explode( ',', \get_option( 'eeab_disabled_pages_array' . $banner_id ) ) );
                            $frontpage_id = \get_option( 'page_on_front' );
                            
                            // Homepage checkbox - uses special "home" identifier.
                            $checked = in_array( 'home', $disabled_pages_array, true ) ? 'checked' : '';
                            echo '<label style="display: block; margin-bottom: 5px;">';
                            echo '<input type="checkbox" ' . esc_attr( $checked ) . ' value="home"> ';
                            echo '<strong>' . esc_html( \get_option( 'blogname' ) ) . '</strong> (' . esc_html__( 'Homepage', 'topbar-buddy' ) . ')';
                            echo '</label>';
                            
                            // If static front page exists, exclude it from the pages list.
                            $frontpage_id = $frontpage_id ? (int) $frontpage_id : 0;

                            // Other pages.
                            // Get all pages and filter out front page in PHP to avoid performance issues with exclude parameter.
                            $all_pages = \get_pages();
                            $pages = array_filter(
                                $all_pages,
                                function( $page ) use ( $frontpage_id ) {
                                    return (int) $page->ID !== (int) $frontpage_id;
                                }
                            );
                            foreach ( $pages as $page ) {
                                $checked = in_array( (string) $page->ID, $disabled_pages_array, true ) ? 'checked' : '';
                                echo '<label style="display: block; margin-bottom: 5px;">';
                                echo '<input type="checkbox" ' . esc_attr( $checked ) . ' value="' . esc_attr( $page->ID ) . '"> ';
                                echo esc_html( $page->post_title ) . ' | <code>' . esc_html( \get_page_uri( $page->ID ) ) . '</code>';
                                echo '</label>';
                            }
                            ?>
                        </div>
                        <input type="hidden" id="eeab_disabled_pages_array<?php echo esc_attr( $banner_id ); ?>" 
                               name="eeab_disabled_pages_array<?php echo esc_attr( $banner_id ); ?>" 
                               value="<?php echo esc_attr( \get_option( 'eeab_disabled_pages_array' . $banner_id ) ); ?>" />
                    </div>
                </td>
            </tr>

            <!-- Custom CSS (Advanced) -->
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Custom CSS', 'topbar-buddy' ); ?>
                    <div class="sb-field-description" style="color: #d63384;">
                        <strong><?php esc_html_e( 'Advanced:', 'topbar-buddy' ); ?></strong> <?php esc_html_e( 'Only use if you know CSS', 'topbar-buddy' ); ?>
                    </div>
                </th>
                <td>
                    <div class="sb-css-grid">
                        <div class="sb-css-section">
                            <div class="sb-css-label">.topbar-buddy<?php echo esc_html( $banner_id ); ?> {</div>
                            <textarea id="topbar_buddy_custom_css<?php echo esc_attr( $banner_id ); ?>" 
                                      name="topbar_buddy_custom_css<?php echo esc_attr( $banner_id ); ?>" 
                                      class="sb-css-textarea code"
                                      placeholder="padding: 20px; border-radius: 8px;"><?php echo esc_textarea( \get_option( 'topbar_buddy_custom_css' . $banner_id ) ); ?></textarea>
                            <div>}</div>
                        </div>
                        
                        <div class="sb-css-section">
                            <div class="sb-css-label">.topbar-buddy-text<?php echo esc_html( $banner_id ); ?> {</div>
                            <textarea id="topbar_buddy_text_custom_css<?php echo esc_attr( $banner_id ); ?>" 
                                      name="topbar_buddy_text_custom_css<?php echo esc_attr( $banner_id ); ?>" 
                                      class="sb-css-textarea code"
                                      placeholder="font-weight: 600;"><?php echo esc_textarea( \get_option( 'topbar_buddy_text_custom_css' . $banner_id ) ); ?></textarea>
                            <div>}</div>
                        </div>
                        
                        <div class="sb-css-section">
                            <div class="sb-css-label">.topbar-buddy-button<?php echo esc_html( $banner_id ); ?> {</div>
                            <textarea id="topbar_buddy_button_css<?php echo esc_attr( $banner_id ); ?>" 
                                      name="topbar_buddy_button_css<?php echo esc_attr( $banner_id ); ?>" 
                                      class="sb-css-textarea code"
                                      placeholder="font-size: 20px;"><?php echo esc_textarea( \get_option( 'topbar_buddy_button_css' . $banner_id ) ); ?></textarea>
                            <div>}</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <div class="sb-css-label">.topbar-buddy-scrolling<?php echo esc_html( $banner_id ); ?> {</div>
                        <textarea id="topbar_buddy_scrolling_custom_css<?php echo esc_attr( $banner_id ); ?>" 
                                  name="topbar_buddy_scrolling_custom_css<?php echo esc_attr( $banner_id ); ?>" 
                                  class="sb-css-textarea code" 
                                  style="width: 100%;"
                                  placeholder="opacity: 0.9;"><?php echo esc_textarea( \get_option( 'topbar_buddy_scrolling_custom_css' . $banner_id ) ); ?></textarea>
                        <div>}</div>
                        <div class="sb-field-description"><?php esc_html_e( 'CSS applied when user scrolls down the page', 'topbar-buddy' ); ?></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>


