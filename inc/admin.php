<?php
/**
 * Admin page registration, script enqueueing, and settings page rendering.
 *
 * @package Warder_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin settings page under the Settings menu.
 */
function warder_add_options_page() {
	add_options_page(
		'Warder Cookie Consent',
		'Warder Consent',
		'manage_options',
		'warder-cookie-consent',
		'warder_render_options_page'
	);
}
add_action( 'admin_menu', 'warder_add_options_page' );

/**
 * Enqueues jQuery-dependent admin scripts for the plugin settings page.
 *
 * @param string $hook The current admin page hook suffix.
 */
function warder_enqueue_admin_scripts( $hook ) {
	if ( 'settings_page_warder-cookie-consent' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'warder-admin',
		plugin_dir_url( WARDER_PLUGIN_FILE ) . 'assets/js/admin.js',
		array( 'jquery' ),
		WARDER_VERSION,
		true
	);

	wp_localize_script(
		'warder-admin',
		'warderAdmin',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'save'    => __( 'Save Settings', 'warder-cookie-consent' ),
			'saving'  => __( 'Saving…', 'warder-cookie-consent' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'warder_enqueue_admin_scripts' );

/**
 * Renders the plugin settings page in the WordPress admin.
 */
function warder_render_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Nonce already verified by options.php before the redirect that sets this param.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$settings_updated = isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) );
	if ( $settings_updated ) {
		delete_transient( 'warder_options_cache' );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$warder_notice = isset( $_GET['warder_notice'] ) ? sanitize_key( wp_unslash( $_GET['warder_notice'] ) ) : '';

	$options         = get_option( 'warder_options', array() );
	$default_options = warder_get_default_options();
	$options         = wp_parse_args( $options, $default_options );

	if ( ! isset( $options['cookie_categories'] ) || ! is_array( $options['cookie_categories'] ) ) {
		$options['cookie_categories'] = $default_options['cookie_categories'];
	}

	$options = warder_handle_admin_actions( $options );

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( $settings_updated || 'saved' === $warder_notice ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><strong><?php esc_html_e( 'Settings saved successfully.', 'warder-cookie-consent' ); ?></strong></p>
		</div>
		<?php endif; ?>

		<!-- MAIN SETTINGS FORM -->
		<form method="post" action="options.php" id="warder-main-settings-form">
			<?php settings_fields( 'warder_options_group' ); ?>

			<!-- General Settings Section -->
			<h2><?php esc_html_e( 'General Settings', 'warder-cookie-consent' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Plugin', 'warder-cookie-consent' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[enabled]" <?php checked( $options['enabled'], true ); ?> />
							<?php esc_html_e( 'Display the cookie consent banner on the frontend', 'warder-cookie-consent' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Language', 'warder-cookie-consent' ); ?></th>
					<td>
						<select name="warder_options[current_lang]">
							<option value="en" <?php selected( $options['current_lang'], 'en' ); ?>><?php esc_html_e( 'English', 'warder-cookie-consent' ); ?></option>
							<option value="fr" <?php selected( $options['current_lang'], 'fr' ); ?>><?php esc_html_e( 'French', 'warder-cookie-consent' ); ?></option>
							<option value="de" <?php selected( $options['current_lang'], 'de' ); ?>><?php esc_html_e( 'German', 'warder-cookie-consent' ); ?></option>
							<option value="es" <?php selected( $options['current_lang'], 'es' ); ?>><?php esc_html_e( 'Spanish', 'warder-cookie-consent' ); ?></option>
							<option value="it" <?php selected( $options['current_lang'], 'it' ); ?>><?php esc_html_e( 'Italian', 'warder-cookie-consent' ); ?></option>
							<option value="nl" <?php selected( $options['current_lang'], 'nl' ); ?>><?php esc_html_e( 'Dutch', 'warder-cookie-consent' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( "Default language for the cookie consent banner. For more languages, you'll need to modify the src/index.js file.", 'warder-cookie-consent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-clear Cookies', 'warder-cookie-consent' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[autoclear_cookies]" <?php checked( $options['autoclear_cookies'], true ); ?> />
							<?php esc_html_e( 'Automatically clear cookies when user rejects them', 'warder-cookie-consent' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Page Scripts', 'warder-cookie-consent' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[page_scripts]" <?php checked( $options['page_scripts'], true ); ?> />
							<?php esc_html_e( 'Control script execution based on user consent', 'warder-cookie-consent' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Preferences Toggle Button', 'warder-cookie-consent' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[show_preferences_toggle]" <?php checked( $options['show_preferences_toggle'], true ); ?> />
							<?php esc_html_e( 'Show a floating button to reopen cookie preferences', 'warder-cookie-consent' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Displays a cookie icon button that lets users revisit their consent choices at any time.', 'warder-cookie-consent' ); ?></p>
						<br>
						<select name="warder_options[preferences_toggle_position]">
							<option value="bottom-right" <?php selected( $options['preferences_toggle_position'], 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'warder-cookie-consent' ); ?></option>
							<option value="bottom-left" <?php selected( $options['preferences_toggle_position'], 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'warder-cookie-consent' ); ?></option>
							<option value="top-right" <?php selected( $options['preferences_toggle_position'], 'top-right' ); ?>><?php esc_html_e( 'Top Right', 'warder-cookie-consent' ); ?></option>
							<option value="top-left" <?php selected( $options['preferences_toggle_position'], 'top-left' ); ?>><?php esc_html_e( 'Top Left', 'warder-cookie-consent' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Corner where the floating button appears.', 'warder-cookie-consent' ); ?></p>
					</td>
				</tr>
			</table>

			<!-- Consent Modal Section -->
			<h2><?php esc_html_e( 'Consent Modal', 'warder-cookie-consent' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Title', 'warder-cookie-consent' ); ?></th>
					<td>
						<input type="text" name="warder_options[title]" value="<?php echo esc_attr( $options['title'] ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Title displayed in the cookie consent banner.', 'warder-cookie-consent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Description', 'warder-cookie-consent' ); ?></th>
					<td>
						<textarea name="warder_options[description]" rows="4" class="large-text"><?php echo esc_textarea( $options['description'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Main description explaining cookie usage on your site.', 'warder-cookie-consent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Primary Button', 'warder-cookie-consent' ); ?></th>
					<td>
						<input type="text" name="warder_options[primary_btn_text]" value="<?php echo esc_attr( $options['primary_btn_text'] ); ?>" class="regular-text" />
						<select name="warder_options[primary_btn_role]">
							<option value="accept_all" <?php selected( $options['primary_btn_role'], 'accept_all' ); ?>><?php esc_html_e( 'Accept All', 'warder-cookie-consent' ); ?></option>
							<option value="accept_selected" <?php selected( $options['primary_btn_role'], 'accept_selected' ); ?>><?php esc_html_e( 'Accept Selected', 'warder-cookie-consent' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Primary action button for the consent banner.', 'warder-cookie-consent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Secondary Button', 'warder-cookie-consent' ); ?></th>
					<td>
						<input type="text" name="warder_options[secondary_btn_text]" value="<?php echo esc_attr( $options['secondary_btn_text'] ); ?>" class="regular-text" />
						<select name="warder_options[secondary_btn_role]">
							<option value="accept_necessary" <?php selected( $options['secondary_btn_role'], 'accept_necessary' ); ?>><?php esc_html_e( 'Accept Necessary', 'warder-cookie-consent' ); ?></option>
							<option value="settings" <?php selected( $options['secondary_btn_role'], 'settings' ); ?>><?php esc_html_e( 'Settings', 'warder-cookie-consent' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Secondary action button for the consent banner.', 'warder-cookie-consent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Privacy Policy URL', 'warder-cookie-consent' ); ?></th>
					<td>
						<input type="text" name="warder_options[privacy_policy_url]" value="<?php echo esc_attr( $options['privacy_policy_url'] ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Link to your privacy policy page. Default: #privacy-policy', 'warder-cookie-consent' ); ?></p>
					</td>
				</tr>
			</table>

			<!-- Cookie Categories Section -->
			<h2><?php esc_html_e( 'Cookie Categories', 'warder-cookie-consent' ); ?></h2>
			<p><?php esc_html_e( 'Configure cookie categories and specific cookies to be blocked until consent is given.', 'warder-cookie-consent' ); ?></p>

			<?php
			if ( isset( $options['cookie_categories'] ) && is_array( $options['cookie_categories'] ) ) {
				foreach ( $options['cookie_categories'] as $category_id => $category ) :
					?>
				<div class="warder-category-section" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
					<h3 style="margin-top: 0;">
						<?php echo esc_html( $category['title'] ); ?> (<?php echo esc_html( $category_id ); ?>)
						<?php if ( 'necessary' !== $category_id ) : ?>
							<a href="
							<?php
							echo esc_url(
								wp_nonce_url(
									add_query_arg(
										array(
											'page'     => 'warder-cookie-consent',
											'action'   => 'delete_category',
											'category' => $category_id,
										),
										admin_url( 'options-general.php' )
									),
									'delete_category_' . $category_id
								)
							);
							?>
							" class="button button-small" style="float: right;" onclick="return confirm('<?php echo esc_js( __( 'Delete this entire category and its cookies?', 'warder-cookie-consent' ) ); ?>');"><?php esc_html_e( 'Delete Category', 'warder-cookie-consent' ); ?></a>
						<?php endif; ?>
					</h3>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Title', 'warder-cookie-consent' ); ?></th>
							<td>
								<input type="text"
										name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][title]"
										value="<?php echo esc_attr( $category['title'] ); ?>"
										class="regular-text warder-category-title-field"
										id="warder-category-<?php echo esc_attr( $category_id ); ?>-title" />
								<p class="description"><?php esc_html_e( 'The name displayed to users in the consent preferences panel.', 'warder-cookie-consent' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Description', 'warder-cookie-consent' ); ?></th>
							<td>
								<textarea name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][description]"
									rows="2" class="large-text"><?php echo esc_textarea( $category['description'] ); ?></textarea>
								<p class="description"><?php esc_html_e( "Explanation of what these cookies do and why they're used.", 'warder-cookie-consent' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Settings', 'warder-cookie-consent' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][enabled]"
										<?php checked( $category['enabled'], true ); ?>
										<?php
										if ( 'necessary' === $category_id ) {
											echo 'disabled';
										}
										?>
										/>
									<?php esc_html_e( 'Enabled by default', 'warder-cookie-consent' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'If checked, this category will be pre-selected when the user sees the banner.', 'warder-cookie-consent' ); ?></p>
								<br>
								<label>
									<input type="checkbox" name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][readonly]"
										<?php checked( $category['readonly'], true ); ?>
										<?php
										if ( 'necessary' === $category_id ) {
											echo 'disabled';
										}
										?>
										/>
									<?php esc_html_e( 'Read-only (user cannot change)', 'warder-cookie-consent' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'If checked, users won\'t be able to toggle this category off. The "necessary" category is always read-only.', 'warder-cookie-consent' ); ?></p>
							</td>
						</tr>
					</table>

					<h4><?php esc_html_e( 'Cookies in this category', 'warder-cookie-consent' ); ?></h4>

					<?php if ( ! empty( $category['cookies'] ) ) : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Cookie Name / Pattern', 'warder-cookie-consent' ); ?></th>
									<th><?php esc_html_e( 'Type', 'warder-cookie-consent' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'warder-cookie-consent' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $category['cookies'] as $index => $cookie ) : ?>
									<tr>
										<td>
											<input type="hidden"
												name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][cookies][<?php echo esc_attr( $index ); ?>][name]"
												value="<?php echo esc_attr( $cookie['name'] ); ?>" />
											<?php echo esc_html( $cookie['name'] ); ?>
										</td>
										<td>
											<input type="hidden"
												name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][cookies][<?php echo esc_attr( $index ); ?>][is_regex]"
												value="<?php echo esc_attr( $cookie['is_regex'] ? '1' : '0' ); ?>" />
											<?php echo $cookie['is_regex'] ? esc_html__( 'Regular Expression', 'warder-cookie-consent' ) : esc_html__( 'Exact Match', 'warder-cookie-consent' ); ?>
										</td>
										<td>
											<a href="
											<?php
											echo esc_url(
												wp_nonce_url(
													add_query_arg(
														array(
															'page'         => 'warder-cookie-consent',
															'action'       => 'delete_cookie',
															'category'     => $category_id,
															'cookie_index' => $index,
														),
														admin_url( 'options-general.php' )
													),
													'delete_cookie_' . $category_id . '_' . $index
												)
											);
											?>
											" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this cookie?', 'warder-cookie-consent' ) ); ?>');">
												<?php esc_html_e( 'Remove', 'warder-cookie-consent' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php esc_html_e( 'No cookies defined for this category yet.', 'warder-cookie-consent' ); ?></p>
					<?php endif; ?>

					<div style="margin-top: 10px;">
						<button type="button" class="button show-add-cookie-form" data-category="<?php echo esc_attr( $category_id ); ?>">
							<?php esc_html_e( 'Add Cookie to this Category', 'warder-cookie-consent' ); ?>
						</button>
					</div>

				</div>
					<?php
				endforeach;
			} else {
				echo '<p>' . esc_html__( 'No cookie categories found. Default categories will be created when you save settings.', 'warder-cookie-consent' ) . '</p>';
			}
			?>

			<!-- Submit button for main settings -->
			<?php submit_button( __( 'Save All Settings', 'warder-cookie-consent' ), 'primary', 'submit', false ); ?>
		</form>

		<?php
		// Add-cookie containers — one per category, outside the main settings form so
		// inputs submit cleanly without needing the HTML5 `form` attribute.
		if ( isset( $options['cookie_categories'] ) && is_array( $options['cookie_categories'] ) ) :
			foreach ( $options['cookie_categories'] as $form_category_id => $form_category ) :
				?>
				<div class="warder-add-cookie-form-container" style="margin: 10px 0; display: none;" id="warder-add-cookie-container-<?php echo esc_attr( $form_category_id ); ?>">
					<div style="padding: 15px; background: #f5f5f5; border: 1px solid #ddd;">
						<form method="post" action="" id="warder-add-cookie-form-<?php echo esc_attr( $form_category_id ); ?>">
							<?php wp_nonce_field( 'warder_add_cookie', 'warder_cookie_nonce' ); ?>
							<input type="hidden" name="category_id" value="<?php echo esc_attr( $form_category_id ); ?>" />
							<h4>
								<?php
								/* translators: %s: cookie category title. */
								printf( esc_html__( 'Add Cookie to "%s"', 'warder-cookie-consent' ), esc_html( $form_category['title'] ) );
								?>
							</h4>
							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Cookie Name/Pattern', 'warder-cookie-consent' ); ?></th>
									<td>
										<input type="text" name="cookie_name" placeholder="<?php esc_attr_e( 'e.g., _ga or /^_ga/', 'warder-cookie-consent' ); ?>" class="regular-text" required />
										<p class="description"><?php esc_html_e( 'Enter a specific cookie name or a pattern to match multiple cookies.', 'warder-cookie-consent' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Match Type', 'warder-cookie-consent' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="is_regex" />
											<?php esc_html_e( 'Regular Expression', 'warder-cookie-consent' ); ?>
										</label>
										<p class="description">
											<?php esc_html_e( 'Leave unchecked for exact cookie names (e.g. _gid).', 'warder-cookie-consent' ); ?>
											<br />
											<?php esc_html_e( 'Tick this and wrap the value in /slashes/ to match multiple cookies with a pattern (e.g. /^_ga/).', 'warder-cookie-consent' ); ?>
										</p>
									</td>
								</tr>
							</table>
							<p>
								<input type="submit" name="warder_add_cookie" value="<?php esc_attr_e( 'Add Cookie', 'warder-cookie-consent' ); ?>" class="button button-primary" />
								<button type="button" class="button button-secondary cancel-add-cookie"><?php esc_html_e( 'Cancel', 'warder-cookie-consent' ); ?></button>
							</p>
							<h5><?php esc_html_e( 'Common Cookie Patterns', 'warder-cookie-consent' ); ?></h5>
							<ul class="cookie-pattern-examples">
								<li><strong>Google Analytics:</strong> <code>/^_ga/</code> <?php esc_html_e( '(regex)', 'warder-cookie-consent' ); ?>, <code>_gid</code>, <code>_gat</code></li>
								<li><strong>Facebook:</strong> <code>/^_fb/</code> <?php esc_html_e( '(regex)', 'warder-cookie-consent' ); ?>, <code>/^fb_/</code> <?php esc_html_e( '(regex)', 'warder-cookie-consent' ); ?>, <code>_fbp</code></li>
								<li><strong>Google Ads:</strong> <code>_gcl_au</code>, <code>/^_gcl_/</code> <?php esc_html_e( '(regex)', 'warder-cookie-consent' ); ?></li>
								<li><strong>Matomo:</strong> <code>/^_pk_/</code> <?php esc_html_e( '(regex)', 'warder-cookie-consent' ); ?>, <code>/^mtm_/</code> <?php esc_html_e( '(regex)', 'warder-cookie-consent' ); ?></li>
							</ul>
						</form>
					</div>
				</div>
				<?php
			endforeach;
		endif;
		?>

		<!-- SEPARATE FORMS FOR ADDING COOKIES AND CATEGORIES -->
		<div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border: 1px solid #ddd;">
			<h3><?php esc_html_e( 'Add New Category', 'warder-cookie-consent' ); ?></h3>
			<form method="post" action="" id="warder-add-category-form">
				<?php wp_nonce_field( 'warder_add_category', 'warder_category_nonce' ); ?>
				<input type="text" name="new_category_id" placeholder="<?php esc_attr_e( 'New category ID (e.g. marketing)', 'warder-cookie-consent' ); ?>" class="regular-text" required />
				<input type="submit" name="warder_add_category" value="<?php esc_attr_e( 'Add New Category', 'warder-cookie-consent' ); ?>" class="button button-secondary" />
				<p class="description"><?php esc_html_e( 'Common categories: marketing, preferences, functional, etc.', 'warder-cookie-consent' ); ?></p>
			</form>
		</div>
	</div>

	<?php
}

/**
 * Displays an admin notice prompting the user to configure the plugin.
 */
function warder_admin_notices() {
	global $pagenow;

	// Do not show the notice on the plugin settings page itself.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'warder-cookie-consent' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
		return;
	}

	// Self-suppress once the user has saved settings at least once.
	if ( get_option( 'warder_options_last_updated' ) ) {
		return;
	}

	if ( function_exists( 'get_plugin_data' ) ) {
		$plugin_data = get_plugin_data( WARDER_PLUGIN_FILE );
		$plugin_name = $plugin_data['Name'];
	} else {
		$plugin_name = 'Warder Cookie Consent';
	}

	echo '<div class="notice notice-info is-dismissible">';
	echo '<p>' . sprintf(
		/* translators: 1: Plugin name, 2: HTML link to settings page. */
		esc_html__( 'Thank you for installing %1$s! Please configure your settings on the %2$s.', 'warder-cookie-consent' ),
		esc_html( $plugin_name ),
		'<a href="' . esc_url( admin_url( 'options-general.php?page=warder-cookie-consent' ) ) . '">' . esc_html__( 'settings page', 'warder-cookie-consent' ) . '</a>'
	) . '</p>';
	echo '</div>';
}
add_action( 'admin_notices', 'warder_admin_notices' );

/**
 * Renders an input field for a cookie category title with the correct CSS class.
 *
 * @param string $category_id The category identifier.
 * @param string $title       The current category title.
 */
function warder_render_category_title_field( $category_id, $title ) {
	?>
	<input type="text"
			name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][title]"
			value="<?php echo esc_attr( $title ); ?>"
			class="regular-text warder-category-title-field"
			data-category-id="<?php echo esc_attr( $category_id ); ?>" />
	<p class="description"><?php esc_html_e( 'The name displayed to users in the consent preferences panel.', 'warder-cookie-consent' ); ?></p>
	<?php
}
