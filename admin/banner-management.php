<?php
/**
 * Banner Management Page
 *
 * @package TopBar Buddy
 * @since 2.0.0
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

// Get actual timezone object for date operations
try {
	if ( ! empty( $topbar_buddy_timezone_string ) ) {
		$wp_timezone_obj = new \DateTimeZone( $topbar_buddy_timezone_string );
	} else {
		// For GMT offsets, use UTC as fallback
		$wp_timezone_obj = new \DateTimeZone( 'UTC' );
	}
} catch ( \Exception $e ) {
	$wp_timezone_obj = new \DateTimeZone( 'UTC' );
}

// Handle banner actions
if ( ! class_exists( 'ElearningEvolve\TopBarBuddy\BannerManager' ) ) {
	wp_die( esc_html__( 'Banner Manager class not found. Please check plugin installation.', 'topbar-buddy' ) );
}

$topbar_buddy_banner_manager = new \ElearningEvolve\TopBarBuddy\BannerManager();
$topbar_buddy_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
$banner_id = isset( $_GET['banner_id'] ) ? intval( $_GET['banner_id'] ) : 0;
$topbar_buddy_message = '';

// Handle form submissions
if ( ! empty( $_POST ) && isset( $_POST['topbar_buddy_nonce'] ) ) {
	// Verify nonce
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['topbar_buddy_nonce'] ) ), 'topbar_buddy_banner_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'topbar-buddy' ) );
	}

	if ( isset( $_POST['create_banner'] ) || isset( $_POST['update_banner'] ) ) {
		// Sanitize and prepare banner data
		$topbar_buddy_banner_data = array(
			'name' => sanitize_text_field( wp_unslash( $_POST['banner_name'] ?? '' ) ),
			'content' => wp_kses_post( wp_unslash( $_POST['banner_content'] ?? '' ) ),
			'background_color' => sanitize_hex_color( wp_unslash( $_POST['background_color'] ?? '' ) ) ?: '#000000',
			'text_color' => sanitize_hex_color( wp_unslash( $_POST['text_color'] ?? '' ) ) ?: '#ffffff',
			'link_color' => sanitize_hex_color( wp_unslash( $_POST['link_color'] ?? '' ) ) ?: '#f16521',
			'close_color' => sanitize_hex_color( wp_unslash( $_POST['close_color'] ?? '' ) ) ?: '#ffffff',
			'font_size' => sanitize_text_field( wp_unslash( $_POST['font_size'] ?? '' ) ),
			'position' => sanitize_text_field( wp_unslash( $_POST['position'] ?? 'fixed' ) ),
			'z_index' => sanitize_text_field( wp_unslash( $_POST['z_index'] ?? '999999' ) ),
			'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
			'disabled_on_posts' => isset( $_POST['disabled_on_posts'] ) ? 1 : 0,
			'disabled_pages' => sanitize_text_field( wp_unslash( $_POST['disabled_pages'] ?? '' ) ),
			'disabled_paths' => sanitize_text_field( wp_unslash( $_POST['disabled_paths'] ?? '' ) ),
			'close_button_enabled' => isset( $_POST['close_button_enabled'] ) ? 1 : 0,
			'close_button_expiration' => intval( wp_unslash( $_POST['close_button_expiration'] ?? 24 ) ),
			'custom_css' => wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ?? '' ) ),
			'scrolling_custom_css' => wp_strip_all_tags( wp_unslash( $_POST['scrolling_custom_css'] ?? '' ) ),
			'text_custom_css' => wp_strip_all_tags( wp_unslash( $_POST['text_custom_css'] ?? '' ) ),
			'button_css' => wp_strip_all_tags( wp_unslash( $_POST['button_css'] ?? '' ) ),
		);

		// Handle dates - convert to proper MySQL datetime format in WordPress timezone
		if ( ! empty( $_POST['start_date'] ) ) {
			$topbar_buddy_start_input = sanitize_text_field( wp_unslash( $_POST['start_date'] ) );
			try {
				// Parse the input date in WordPress timezone
				$topbar_buddy_start_dt = new \DateTime( $topbar_buddy_start_input, $wp_timezone_obj );
				// Store in MySQL format
				$topbar_buddy_banner_data['start_date'] = $topbar_buddy_start_dt->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $e ) {
				$topbar_buddy_banner_data['start_date'] = null;
			}
		} else {
			$topbar_buddy_banner_data['start_date'] = null;
		}

		if ( ! empty( $_POST['end_date'] ) ) {
			$topbar_buddy_end_input = sanitize_text_field( wp_unslash( $_POST['end_date'] ) );
			try {
				// Parse the input date in WordPress timezone
				$topbar_buddy_end_dt = new \DateTime( $topbar_buddy_end_input, $wp_timezone_obj );
				// Store in MySQL format
				$topbar_buddy_banner_data['end_date'] = $topbar_buddy_end_dt->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $e ) {
				$topbar_buddy_banner_data['end_date'] = null;
			}
		} else {
			$topbar_buddy_banner_data['end_date'] = null;
		}

		if ( isset( $_POST['create_banner'] ) ) {
			$topbar_buddy_result = $topbar_buddy_banner_manager->create_banner( $topbar_buddy_banner_data );
			if ( $topbar_buddy_result ) {
				$topbar_buddy_message = __( 'Banner created successfully!', 'topbar-buddy' );
			} else {
				$topbar_buddy_message = __( 'Error creating banner.', 'topbar-buddy' );
			}
		} else {
			$topbar_buddy_result = $topbar_buddy_banner_manager->update_banner( $banner_id, $topbar_buddy_banner_data );
			if ( $topbar_buddy_result ) {
				$topbar_buddy_message = __( 'Banner updated successfully!', 'topbar-buddy' );
			} else {
				$topbar_buddy_message = __( 'Error updating banner.', 'topbar-buddy' );
			}
		}
	}
}

// Handle delete action
if ( $topbar_buddy_action === 'delete' && $banner_id ) {
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_banner_' . $banner_id ) ) {
		wp_die( esc_html__( 'Security check failed.', 'topbar-buddy' ) );
	}
	
	if ( $topbar_buddy_banner_manager->delete_banner( $banner_id ) ) {
		$topbar_buddy_message = __( 'Banner deleted successfully!', 'topbar-buddy' );
	} else {
		$topbar_buddy_message = __( 'Error deleting banner.', 'topbar-buddy' );
	}
	$topbar_buddy_action = '';
	$banner_id = 0;
}

// Get banner for editing
$topbar_buddy_editing_banner = null;
if ( $topbar_buddy_action === 'edit' && $banner_id ) {
	$topbar_buddy_editing_banner = $topbar_buddy_banner_manager->get_banner( $banner_id );
}

// Get all banners
$topbar_buddy_banners = $topbar_buddy_banner_manager->get_banners();
?>

<div class="wrap topbar-buddy-admin">
	<div class="sb-header">
		<h1><?php esc_html_e( 'TopBar Buddy - Multiple Banners', 'topbar-buddy' ); ?></h1>
	</div>

	<?php if ( $topbar_buddy_message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $topbar_buddy_message ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Banner List -->
	<?php if ( $topbar_buddy_action !== 'add' && $topbar_buddy_action !== 'edit' ) : ?>
		<!-- Debug Info -->
		<?php 
		$topbar_buddy_active_front_end_banners = $topbar_buddy_banner_manager->get_current_active_banners();
		$topbar_buddy_current_time_obj = new \DateTime( 'now', $wp_timezone_obj );
		?>
		<div class="notice notice-info" style="background: #f0f8ff; border-left-color: #0073aa;">
			<h3 style="margin-top: 10px;"><?php esc_html_e( 'Front-End Status', 'topbar-buddy' ); ?></h3>
			<p>
				<strong><?php esc_html_e( 'Current Time:', 'topbar-buddy' ); ?></strong> 
				<?php echo esc_html( $topbar_buddy_current_time_obj->format( 'Y-m-d H:i:s' ) ); ?> (<?php echo esc_html( $wp_timezone ); ?>)
			</p>
			<p>
				<strong><?php esc_html_e( 'Banners Currently Showing on Site:', 'topbar-buddy' ); ?></strong>
				<?php echo count( $topbar_buddy_active_front_end_banners ); ?> banner(s)
			</p>
			<?php if ( ! empty( $active_front_end_banners ) ) : ?>
				<ul style="margin-left: 20px;">
					<?php foreach ( $active_front_end_banners as $ab ) : ?>
						<li>
							<strong><?php echo esc_html( $topbar_buddy_ab->name ); ?></strong> (ID: <?php echo esc_html( $topbar_buddy_ab->id ); ?>)
							<?php if ( $topbar_buddy_ab->start_date ) : ?>
								- Start: <?php echo esc_html( $topbar_buddy_ab->start_date ); ?>
								<?php
								$topbar_buddy_start_check = new \DateTime( $topbar_buddy_ab->start_date, $wp_timezone_obj );
								$topbar_buddy_is_started = $topbar_buddy_current_time_obj >= $topbar_buddy_start_check;
								?>
								<span style="color: <?php echo esc_attr( $topbar_buddy_is_started ? 'green' : 'orange' ); ?>;">
									(<?php echo esc_html( $topbar_buddy_is_started ? '✓ Started' : '⏳ Not yet' ); ?>)
								</span>
							<?php endif; ?>
							<?php if ( $topbar_buddy_ab->end_date ) : ?>
								- End: <?php echo esc_html( $topbar_buddy_ab->end_date ); ?>
								<?php
								$topbar_buddy_end_check = new \DateTime( $topbar_buddy_ab->end_date, $wp_timezone_obj );
								$topbar_buddy_is_not_ended = $topbar_buddy_current_time_obj < $topbar_buddy_end_check;
								?>
								<span style="color: <?php echo esc_attr( $topbar_buddy_is_not_ended ? 'green' : 'red' ); ?>;">
									(<?php echo esc_html( $topbar_buddy_is_not_ended ? '✓ Active' : '✗ Ended' ); ?>)
								</span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p style="color: #d63638; font-weight: bold;">
					<?php esc_html_e( '⚠️ No banners are currently active on your site!', 'topbar-buddy' ); ?>
				</p>
				<p style="margin-left: 20px;">
					<?php esc_html_e( 'Check all your banners below. Banners must meet ALL these conditions:', 'topbar-buddy' ); ?>
				</p>
				<ul style="margin-left: 40px;">
					<li><?php esc_html_e( '✓ "Active" checkbox is checked', 'topbar-buddy' ); ?></li>
					<li><?php esc_html_e( '✓ Start date is empty OR current time is after start date', 'topbar-buddy' ); ?></li>
					<li><?php esc_html_e( '✓ End date is empty OR current time is before end date', 'topbar-buddy' ); ?></li>
				</ul>
			<?php endif; ?>
		</div>
		
		<div class="sb-banners-list">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
				<h2><?php esc_html_e( 'Your Banners', 'topbar-buddy' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=topbar-buddy-banners&action=add' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Add New Banner', 'topbar-buddy' ); ?>
				</a>
			</div>

			<?php if ( empty( $topbar_buddy_banners ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No banners found. Create your first banner!', 'topbar-buddy' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'topbar-buddy' ); ?></th>
							<th><?php esc_html_e( 'Content', 'topbar-buddy' ); ?></th>
							<th><?php esc_html_e( 'Schedule', 'topbar-buddy' ); ?></th>
							<th><?php esc_html_e( 'Status', 'topbar-buddy' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'topbar-buddy' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $topbar_buddy_banners as $topbar_buddy_banner ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $topbar_buddy_banner->name ); ?></strong></td>
								<td><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $topbar_buddy_banner->content ), 10 ) ); ?></td>
								<td>
									<?php if ( $topbar_buddy_banner->start_date ) : ?>
										<strong><?php esc_html_e( 'Start:', 'topbar-buddy' ); ?></strong> <?php echo esc_html( $topbar_buddy_banner->start_date ); ?><br>
									<?php endif; ?>
									<?php if ( $topbar_buddy_banner->end_date ) : ?>
										<strong><?php esc_html_e( 'End:', 'topbar-buddy' ); ?></strong> <?php echo esc_html( $topbar_buddy_banner->end_date ); ?>
									<?php endif; ?>
									<?php if ( ! $topbar_buddy_banner->start_date && ! $topbar_buddy_banner->end_date ) : ?>
										<?php esc_html_e( 'Always active', 'topbar-buddy' ); ?>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $topbar_buddy_banner->is_active ) : ?>
										<?php if ( $topbar_buddy_banner_manager->is_banner_active_by_schedule( $topbar_buddy_banner ) ) : ?>
											<span style="color: green; font-weight: bold;">✓ <?php esc_html_e( 'Active Now', 'topbar-buddy' ); ?></span>
										<?php else : ?>
											<span style="color: orange; font-weight: bold;">⏳ <?php esc_html_e( 'Scheduled', 'topbar-buddy' ); ?></span>
											<br><small>
											<?php
											// Show why it's not active
											if ( ! empty( $topbar_buddy_banner->start_date ) ) {
												$topbar_buddy_start_dt = new \DateTime( $topbar_buddy_banner->start_date, $wp_timezone_obj );
												if ( $topbar_buddy_current_time_obj < $topbar_buddy_start_dt ) {
													$topbar_buddy_diff = $topbar_buddy_start_dt->diff( $topbar_buddy_current_time_obj );
													echo '⏰ Starts in: ';
													if ( $topbar_buddy_diff->days > 0 ) echo esc_html( $topbar_buddy_diff->days ) . ' days ';
													if ( $topbar_buddy_diff->h > 0 ) echo esc_html( $topbar_buddy_diff->h ) . ' hours ';
													if ( $topbar_buddy_diff->i > 0 ) echo esc_html( $topbar_buddy_diff->i ) . ' minutes';
												}
											}
											if ( ! empty( $topbar_buddy_banner->end_date ) ) {
												$topbar_buddy_end_dt = new \DateTime( $topbar_buddy_banner->end_date, $wp_timezone_obj );
												if ( $topbar_buddy_current_time_obj >= $topbar_buddy_end_dt ) {
													echo '✗ Ended';
												}
											}
											?>
											</small>
										<?php endif; ?>
									<?php else : ?>
										<span style="color: red; font-weight: bold;">✗ <?php esc_html_e( 'Inactive', 'topbar-buddy' ); ?></span>
										<br><small><?php esc_html_e( 'Checkbox not checked', 'topbar-buddy' ); ?></small>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=topbar-buddy-banners&action=edit&banner_id=' . $topbar_buddy_banner->id ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'topbar-buddy' ); ?>
									</a>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=topbar-buddy-banners&action=delete&banner_id=' . $topbar_buddy_banner->id ), 'delete_banner_' . $topbar_buddy_banner->id ) ); ?>" 
									   class="button button-small button-link-delete" 
									   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this banner?', 'topbar-buddy' ); ?>')">
										<?php esc_html_e( 'Delete', 'topbar-buddy' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- Banner Form -->
	<?php if ( $action === 'add' || $action === 'edit' ) : ?>
		<div class="sb-banner-form">
			<div style="margin-bottom: 20px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=topbar-buddy-banners' ) ); ?>" class="button">
					← <?php esc_html_e( 'Back to Banners', 'topbar-buddy' ); ?>
				</a>
			</div>

			<h2><?php echo $action === 'add' ? esc_html__( 'Add New Banner', 'topbar-buddy' ) : esc_html__( 'Edit Banner', 'topbar-buddy' ); ?></h2>

			<form method="post" action="">
				<?php wp_nonce_field( 'topbar_buddy_banner_action', 'topbar_buddy_nonce' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="banner_name"><?php esc_html_e( 'Banner Name', 'topbar-buddy' ); ?></label>
						</th>
						<td>
							<input type="text" id="banner_name" name="banner_name" class="regular-text" 
								   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->name ) : ''; ?>" required>
							<p class="description"><?php esc_html_e( 'A descriptive name for this banner (for your reference only).', 'topbar-buddy' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="banner_content"><?php esc_html_e( 'Banner Content', 'topbar-buddy' ); ?></label>
						</th>
						<td>
							<?php
							$topbar_buddy_content = $topbar_buddy_editing_banner ? $topbar_buddy_editing_banner->content : '';
							$topbar_buddy_editor_settings = array(
								'textarea_name' => 'banner_content',
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
							wp_editor( $topbar_buddy_content, 'banner_content', $topbar_buddy_editor_settings );
							?>
							<p class="description"><?php esc_html_e( 'The content to display in your banner. HTML is allowed.', 'topbar-buddy' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Banner Status', 'topbar-buddy' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="is_active" value="1" <?php checked( $topbar_buddy_editing_banner ? $topbar_buddy_editing_banner->is_active : 1, 1 ); ?>>
								<?php esc_html_e( 'Enable this banner', 'topbar-buddy' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Disabled banners will not be displayed on your website.', 'topbar-buddy' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Schedule Banner', 'topbar-buddy' ); ?></th>
						<td>
							<div style="background: #e8f5e9; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #28a745;">
								<strong><?php esc_html_e( 'Site Timezone:', 'topbar-buddy' ); ?></strong> 
								<code><?php echo esc_html( $wp_timezone ); ?></code>
								<div class="sb-field-description" style="margin-top: 5px;">
									<?php esc_html_e( 'All dates and times use your WordPress site timezone setting.', 'topbar-buddy' ); ?>
								</div>
							</div>
							
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
								<div>
									<label for="start_date"><?php esc_html_e( 'Start Date & Time', 'topbar-buddy' ); ?></label>
									<input type="datetime-local" id="start_date" name="start_date" style="width: 100%; padding: 8px; margin-top: 5px;"
										   value="<?php 
											if ( $topbar_buddy_editing_banner && $topbar_buddy_editing_banner->start_date ) {
												try {
													$topbar_buddy_dt = new DateTime( $topbar_buddy_editing_banner->start_date, $wp_timezone_obj );
													echo esc_attr( $topbar_buddy_dt->format( 'Y-m-d\TH:i' ) );
												} catch ( Exception $e ) {
													echo '';
												}
											}
										   ?>" />
									<p class="description"><?php esc_html_e( 'Banner will appear after this date and time. Leave empty to show immediately.', 'topbar-buddy' ); ?></p>
								</div>
								
								<div>
									<label for="end_date"><?php esc_html_e( 'End Date & Time', 'topbar-buddy' ); ?></label>
									<input type="datetime-local" id="end_date" name="end_date" style="width: 100%; padding: 8px; margin-top: 5px;"
										   value="<?php 
											if ( $topbar_buddy_editing_banner && $topbar_buddy_editing_banner->end_date ) {
												try {
													$topbar_buddy_dt = new DateTime( $topbar_buddy_editing_banner->end_date, $wp_timezone_obj );
													echo esc_attr( $topbar_buddy_dt->format( 'Y-m-d\TH:i' ) );
												} catch ( Exception $e ) {
													echo '';
												}
											}
										   ?>" />
									<p class="description"><?php esc_html_e( 'Banner will automatically hide after this date and time. Leave empty to show indefinitely.', 'topbar-buddy' ); ?></p>
								</div>
							</div>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Colors', 'topbar-buddy' ); ?></th>
						<td>
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
								<div>
									<label><?php esc_html_e( 'Background Color', 'topbar-buddy' ); ?></label>
									<input type="color" name="background_color" 
										   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->background_color ) : '#000000'; ?>">
								</div>
								
								<div>
									<label><?php esc_html_e( 'Text Color', 'topbar-buddy' ); ?></label>
									<input type="color" name="text_color" 
										   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->text_color ) : '#ffffff'; ?>">
								</div>
								
								<div>
									<label><?php esc_html_e( 'Link Color', 'topbar-buddy' ); ?></label>
									<input type="color" name="link_color" 
										   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->link_color ) : '#f16521'; ?>">
								</div>
								
								<div>
									<label><?php esc_html_e( 'Close Button Color', 'topbar-buddy' ); ?></label>
									<input type="color" name="close_color" 
										   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->close_color ) : '#ffffff'; ?>">
								</div>
							</div>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Advanced Options', 'topbar-buddy' ); ?></th>
						<td>
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
								<div>
									<label for="font_size"><?php esc_html_e( 'Font Size', 'topbar-buddy' ); ?></label>
									<input type="text" id="font_size" name="font_size" class="regular-text" placeholder="14px" 
										   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->font_size ) : ''; ?>">
								</div>
								
								<div>
									<label for="z_index"><?php esc_html_e( 'Z-Index', 'topbar-buddy' ); ?></label>
									<input type="text" id="z_index" name="z_index" class="regular-text" placeholder="999999" 
										   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->z_index ) : '999999'; ?>">
								</div>

								<div>
									<label for="position"><?php esc_html_e( 'Position', 'topbar-buddy' ); ?></label>
									<select id="position" name="position" class="regular-text">
										<option value="fixed" <?php selected( $topbar_buddy_editing_banner ? $topbar_buddy_editing_banner->position : 'fixed', 'fixed' ); ?>><?php esc_html_e( 'Fixed Top', 'topbar-buddy' ); ?></option>
										<option value="footer" <?php selected( $topbar_buddy_editing_banner ? $topbar_buddy_editing_banner->position : 'fixed', 'footer' ); ?>><?php esc_html_e( 'Fixed Bottom', 'topbar-buddy' ); ?></option>
									</select>
								</div>

								<div>
									<label for="close_button_expiration"><?php esc_html_e( 'Close Button Expiration (hours)', 'topbar-buddy' ); ?></label>
									<input type="number" id="close_button_expiration" name="close_button_expiration" class="regular-text" 
										   placeholder="24" min="1" max="8760"
										   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->close_button_expiration ) : '24'; ?>">
									<p class="description"><?php esc_html_e( 'Hours until closed banner can be shown again.', 'topbar-buddy' ); ?></p>
								</div>
							</div>

							<label>
								<input type="checkbox" name="disabled_on_posts" value="1" <?php checked( $topbar_buddy_editing_banner ? $topbar_buddy_editing_banner->disabled_on_posts : 0, 1 ); ?>>
								<?php esc_html_e( 'Disable on blog posts', 'topbar-buddy' ); ?>
							</label><br>

							<label style="margin-top: 10px; display: block;">
								<input type="checkbox" name="close_button_enabled" value="1" <?php checked( $topbar_buddy_editing_banner ? $topbar_buddy_editing_banner->close_button_enabled : 0, 1 ); ?>>
								<?php esc_html_e( 'Enable close button', 'topbar-buddy' ); ?>
							</label>

							<div style="margin-top: 15px;">
								<label for="disabled_pages"><?php esc_html_e( 'Disabled Page IDs (comma-separated)', 'topbar-buddy' ); ?></label>
								<input type="text" id="disabled_pages" name="disabled_pages" class="regular-text" 
									   value="<?php echo $topbar_buddy_editing_banner ? esc_attr( $topbar_buddy_editing_banner->disabled_pages ) : ''; ?>">
								<p class="description"><?php esc_html_e( 'Page IDs where this banner should not appear.', 'topbar-buddy' ); ?></p>
							</div>

							<div style="margin-top: 15px;">
								<label for="disabled_paths"><?php esc_html_e( 'Disabled URL Paths (comma-separated)', 'topbar-buddy' ); ?></label>
								<input type="text" id="disabled_paths" name="disabled_paths" class="regular-text" 
									   value="<?php echo $editing_banner ? esc_attr( $editing_banner->disabled_paths ) : ''; ?>">
								<p class="description"><?php esc_html_e( 'URL paths where this banner should not appear (e.g., /contact, /about).', 'topbar-buddy' ); ?></p>
							</div>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Custom CSS', 'topbar-buddy' ); ?></th>
						<td>
							<div style="margin-bottom: 15px;">
								<label for="custom_css"><?php esc_html_e( 'Banner CSS', 'topbar-buddy' ); ?></label>
								<textarea id="custom_css" name="custom_css" rows="4" class="large-text code" placeholder="/* Custom CSS for this banner */"><?php echo $editing_banner ? esc_textarea( $editing_banner->custom_css ) : ''; ?></textarea>
								<p class="description"><?php esc_html_e( 'Custom CSS styles for the banner container.', 'topbar-buddy' ); ?></p>
							</div>

							<div style="margin-bottom: 15px;">
								<label for="scrolling_custom_css"><?php esc_html_e( 'Scrolling CSS', 'topbar-buddy' ); ?></label>
								<textarea id="scrolling_custom_css" name="scrolling_custom_css" rows="3" class="large-text code" placeholder="/* CSS when scrolling */"><?php echo $editing_banner ? esc_textarea( $editing_banner->scrolling_custom_css ) : ''; ?></textarea>
								<p class="description"><?php esc_html_e( 'CSS styles applied when page is scrolled.', 'topbar-buddy' ); ?></p>
							</div>

							<div style="margin-bottom: 15px;">
								<label for="text_custom_css"><?php esc_html_e( 'Text CSS', 'topbar-buddy' ); ?></label>
								<textarea id="text_custom_css" name="text_custom_css" rows="3" class="large-text code" placeholder="/* CSS for banner text */"><?php echo $editing_banner ? esc_textarea( $editing_banner->text_custom_css ) : ''; ?></textarea>
								<p class="description"><?php esc_html_e( 'Custom CSS styles for the banner text.', 'topbar-buddy' ); ?></p>
							</div>

							<div style="margin-bottom: 15px;">
								<label for="button_css"><?php esc_html_e( 'Close Button CSS', 'topbar-buddy' ); ?></label>
								<textarea id="button_css" name="button_css" rows="3" class="large-text code" placeholder="/* CSS for close button */"><?php echo $editing_banner ? esc_textarea( $editing_banner->button_css ) : ''; ?></textarea>
								<p class="description"><?php esc_html_e( 'Custom CSS styles for the close button.', 'topbar-buddy' ); ?></p>
							</div>
						</td>
					</tr>
				</table>

				<?php if ( $action === 'add' ) : ?>
					<input type="submit" name="create_banner" class="button button-primary" value="<?php esc_attr_e( 'Create Banner', 'topbar-buddy' ); ?>">
				<?php else : ?>
					<input type="submit" name="update_banner" class="button button-primary" value="<?php esc_attr_e( 'Update Banner', 'topbar-buddy' ); ?>">
				<?php endif; ?>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=topbar-buddy-banners' ) ); ?>" class="button">
					<?php esc_html_e( 'Cancel', 'topbar-buddy' ); ?>
				</a>
			</form>
		</div>
	<?php endif; ?>
</div>