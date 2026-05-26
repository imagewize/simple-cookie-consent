<?php
/**
 * Plugin Name: Warder Cookie Consent
 * Description: GDPR-compliant cookie consent banner with category management and floating preferences toggle.
 * Version: 1.3.1
 * Author: Jasper Frumau
 * Author URI: https://imagewize.com
 * Text Domain: warder-cookie-consent
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Warder_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin settings and adds default options on first activation.
 */
function warder_register_settings() {
	register_setting( 'warder_options_group', 'warder_options', 'warder_validate_options' );

	if ( false === get_option( 'warder_options' ) ) {
		add_option( 'warder_options', warder_get_default_options() );
	}
}
add_action( 'admin_init', 'warder_register_settings' );

/**
 * Returns the canonical default options structure.
 *
 * @return array
 */
function warder_get_default_options() {
	return array(
		'enabled'                     => true,
		'current_lang'                => 'en',
		'autoclear_cookies'           => true,
		'page_scripts'                => true,
		'title'                       => 'We use cookies!',
		'description'                 => 'Hello, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent.',
		'primary_btn_text'            => 'Accept all',
		'primary_btn_role'            => 'accept_all',
		'secondary_btn_text'          => 'Reject all',
		'secondary_btn_role'          => 'accept_necessary',
		'privacy_policy_url'          => '#privacy-policy',
		'show_preferences_toggle'     => true,
		'preferences_toggle_position' => 'bottom-right',
		'cookie_categories'           => array(
			'necessary' => array(
				'title'       => 'Strictly Necessary',
				'description' => 'These cookies are essential for the proper functioning of the website and cannot be disabled.',
				'enabled'     => true,
				'readonly'    => true,
				'cookies'     => array(
					array(
						'name'     => '/^sbjs_/',
						'is_regex' => true,
					),
				),
			),
			'analytics' => array(
				'title'       => 'Performance and Analytics',
				'description' => 'These cookies collect information about how you use our website. All of the data is anonymized and cannot be used to identify you.',
				'enabled'     => false,
				'readonly'    => false,
				'cookies'     => array(
					array(
						'name'     => '/^_ga/',
						'is_regex' => true,
					),
					array(
						'name'     => '_gid',
						'is_regex' => false,
					),
					array(
						'name'     => '_gat',
						'is_regex' => false,
					),
				),
			),
		),
	);
}

/**
 * Sanitizes and validates options before saving to the database.
 *
 * @param array $input Raw input from the settings form.
 * @return array Sanitized options.
 */
function warder_validate_options( $input ) {
	$valid = array();

	$valid['enabled']                     = isset( $input['enabled'] ) ? true : false;
	$valid['current_lang']                = sanitize_text_field( $input['current_lang'] );
	$valid['autoclear_cookies']           = isset( $input['autoclear_cookies'] ) ? true : false;
	$valid['page_scripts']                = isset( $input['page_scripts'] ) ? true : false;
	$valid['title']                       = sanitize_text_field( $input['title'] );
	$valid['description']                 = wp_kses_post( $input['description'] );
	$valid['primary_btn_text']            = sanitize_text_field( $input['primary_btn_text'] );
	$valid['primary_btn_role']            = in_array( $input['primary_btn_role'], array( 'accept_all', 'accept_selected' ), true )
		? $input['primary_btn_role'] : 'accept_all';
	$valid['secondary_btn_text']          = sanitize_text_field( $input['secondary_btn_text'] );
	$valid['secondary_btn_role']          = in_array( $input['secondary_btn_role'], array( 'accept_necessary', 'settings' ), true )
		? $input['secondary_btn_role'] : 'accept_necessary';
	$valid['privacy_policy_url']          = sanitize_text_field( $input['privacy_policy_url'] );
	$valid['show_preferences_toggle']     = isset( $input['show_preferences_toggle'] ) ? true : false;
	$valid['preferences_toggle_position'] = in_array( $input['preferences_toggle_position'], array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ), true )
		? $input['preferences_toggle_position'] : 'bottom-right';

	if ( isset( $input['cookie_categories'] ) && is_array( $input['cookie_categories'] ) ) {
		$valid['cookie_categories'] = array();

		foreach ( $input['cookie_categories'] as $category_id => $category ) {
			$sanitized_id = sanitize_key( $category_id );

			$title = isset( $category['title'] ) ? sanitize_text_field( $category['title'] ) : '';

			$valid['cookie_categories'][ $sanitized_id ] = array(
				'title'       => $title,
				'description' => wp_kses_post( $category['description'] ),
				'enabled'     => isset( $category['enabled'] ) ? true : false,
				'readonly'    => isset( $category['readonly'] ) ? true : false,
				'cookies'     => array(),
			);

			if ( isset( $category['cookies'] ) && is_array( $category['cookies'] ) ) {
				foreach ( $category['cookies'] as $cookie ) {
					if ( ! empty( $cookie['name'] ) ) {
						$valid['cookie_categories'][ $sanitized_id ]['cookies'][] = array(
							'name'     => sanitize_text_field( $cookie['name'] ),
							'is_regex' => isset( $cookie['is_regex'] ) ? true : false,
						);
					}
				}
			}
		}
	}

	return $valid;
}

/**
 * Registers the plugin settings page under the Settings menu.
 */
function warder_add_options_page() {
	add_options_page(
		'Cookie Consent Settings',
		'Cookie Consent',
		'manage_options',
		'warder-cookie-consent',
		'warder_render_options_page'
	);
}
add_action( 'admin_menu', 'warder_add_options_page' );

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

	$options         = get_option( 'warder_options', array() );
	$default_options = warder_get_default_options();
	$options         = wp_parse_args( $options, $default_options );

	if ( ! isset( $options['cookie_categories'] ) || ! is_array( $options['cookie_categories'] ) ) {
		$options['cookie_categories'] = $default_options['cookie_categories'];
	}

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( $settings_updated ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><strong>Settings saved successfully.</strong></p>
		</div>
		<?php endif; ?>

		<!-- MAIN SETTINGS FORM -->
		<form method="post" action="options.php" id="warder-main-settings-form">
			<?php settings_fields( 'warder_options_group' ); ?>

			<!-- General Settings Section -->
			<h2>General Settings</h2>
			<table class="form-table">
				<tr>
					<th scope="row">Enable Plugin</th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[enabled]" <?php checked( $options['enabled'], true ); ?> />
							Display the cookie consent banner on the frontend
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Language</th>
					<td>
						<select name="warder_options[current_lang]">
							<option value="en" <?php selected( $options['current_lang'], 'en' ); ?>>English</option>
							<option value="fr" <?php selected( $options['current_lang'], 'fr' ); ?>>French</option>
							<option value="de" <?php selected( $options['current_lang'], 'de' ); ?>>German</option>
							<option value="es" <?php selected( $options['current_lang'], 'es' ); ?>>Spanish</option>
							<option value="it" <?php selected( $options['current_lang'], 'it' ); ?>>Italian</option>
							<option value="nl" <?php selected( $options['current_lang'], 'nl' ); ?>>Dutch</option>
						</select>
						<p class="description">Default language for the cookie consent banner. For more languages, you'll need to modify the src/index.js file.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Auto-clear Cookies</th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[autoclear_cookies]" <?php checked( $options['autoclear_cookies'], true ); ?> />
							Automatically clear cookies when user rejects them
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Page Scripts</th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[page_scripts]" <?php checked( $options['page_scripts'], true ); ?> />
							Control script execution based on user consent
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Preferences Toggle Button</th>
					<td>
						<label>
							<input type="checkbox" name="warder_options[show_preferences_toggle]" <?php checked( $options['show_preferences_toggle'], true ); ?> />
							Show a floating button to reopen cookie preferences
						</label>
						<p class="description">Displays a cookie icon button that lets users revisit their consent choices at any time.</p>
						<br>
						<select name="warder_options[preferences_toggle_position]">
							<option value="bottom-right" <?php selected( $options['preferences_toggle_position'], 'bottom-right' ); ?>>Bottom Right</option>
							<option value="bottom-left" <?php selected( $options['preferences_toggle_position'], 'bottom-left' ); ?>>Bottom Left</option>
							<option value="top-right" <?php selected( $options['preferences_toggle_position'], 'top-right' ); ?>>Top Right</option>
							<option value="top-left" <?php selected( $options['preferences_toggle_position'], 'top-left' ); ?>>Top Left</option>
						</select>
						<p class="description">Corner where the floating button appears.</p>
					</td>
				</tr>
			</table>

			<!-- Consent Modal Section -->
			<h2>Consent Modal</h2>
			<table class="form-table">
				<tr>
					<th scope="row">Title</th>
					<td>
						<input type="text" name="warder_options[title]" value="<?php echo esc_attr( $options['title'] ); ?>" class="regular-text" />
						<p class="description">Title displayed in the cookie consent banner.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Description</th>
					<td>
						<textarea name="warder_options[description]" rows="4" class="large-text"><?php echo esc_textarea( $options['description'] ); ?></textarea>
						<p class="description">Main description explaining cookie usage on your site.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Primary Button</th>
					<td>
						<input type="text" name="warder_options[primary_btn_text]" value="<?php echo esc_attr( $options['primary_btn_text'] ); ?>" class="regular-text" />
						<select name="warder_options[primary_btn_role]">
							<option value="accept_all" <?php selected( $options['primary_btn_role'], 'accept_all' ); ?>>Accept All</option>
							<option value="accept_selected" <?php selected( $options['primary_btn_role'], 'accept_selected' ); ?>>Accept Selected</option>
						</select>
						<p class="description">Primary action button for the consent banner.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Secondary Button</th>
					<td>
						<input type="text" name="warder_options[secondary_btn_text]" value="<?php echo esc_attr( $options['secondary_btn_text'] ); ?>" class="regular-text" />
						<select name="warder_options[secondary_btn_role]">
							<option value="accept_necessary" <?php selected( $options['secondary_btn_role'], 'accept_necessary' ); ?>>Accept Necessary</option>
							<option value="settings" <?php selected( $options['secondary_btn_role'], 'settings' ); ?>>Settings</option>
						</select>
						<p class="description">Secondary action button for the consent banner.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Privacy Policy URL</th>
					<td>
						<input type="text" name="warder_options[privacy_policy_url]" value="<?php echo esc_attr( $options['privacy_policy_url'] ); ?>" class="regular-text" />
						<p class="description">Link to your privacy policy page. Default: #privacy-policy</p>
					</td>
				</tr>
			</table>

			<!-- Cookie Categories Section -->
			<h2>Cookie Categories</h2>
			<p>Configure cookie categories and specific cookies to be blocked until consent is given.</p>

			<?php
			if ( isset( $options['cookie_categories'] ) && is_array( $options['cookie_categories'] ) ) {
				foreach ( $options['cookie_categories'] as $category_id => $category ) :
					?>
				<div class="warder-category-section" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
					<h3 style="margin-top: 0;"><?php echo esc_html( $category['title'] ); ?> (<?php echo esc_html( $category_id ); ?>)</h3>

					<table class="form-table">
						<tr>
							<th scope="row">Title</th>
							<td>
								<input type="text"
										name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][title]"
										value="<?php echo esc_attr( $category['title'] ); ?>"
										class="regular-text warder-category-title-field"
										id="warder-category-<?php echo esc_attr( $category_id ); ?>-title" />
								<p class="description">The name displayed to users in the consent preferences panel.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Description</th>
							<td>
								<textarea name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][description]"
									rows="2" class="large-text"><?php echo esc_textarea( $category['description'] ); ?></textarea>
								<p class="description">Explanation of what these cookies do and why they're used.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Settings</th>
							<td>
								<label>
									<input type="checkbox" name="warder_options[cookie_categories][<?php echo esc_attr( $category_id ); ?>][enabled]"
										<?php checked( $category['enabled'], true ); ?> />
									Enabled by default
								</label>
								<p class="description">If checked, this category will be pre-selected when the user sees the banner.</p>
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
									Read-only (user cannot change)
								</label>
								<p class="description">If checked, users won't be able to toggle this category off. The "necessary" category is always read-only.</p>
							</td>
						</tr>
					</table>

					<h4>Cookies in this category</h4>

					<?php if ( ! empty( $category['cookies'] ) ) : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th>Cookie Name / Pattern</th>
									<th>Type</th>
									<th>Actions</th>
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
												value="<?php echo esc_attr( $cookie['is_regex'] ? '1' : '' ); ?>" />
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
											" class="button button-small" onclick="return confirm('Are you sure you want to remove this cookie?');">
												Remove
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p>No cookies defined for this category yet.</p>
					<?php endif; ?>

					<div style="margin-top: 10px;">
						<button type="button" class="button show-add-cookie-form" data-category="<?php echo esc_attr( $category_id ); ?>">
							Add Cookie to this Category
						</button>
					</div>
				</div>
					<?php
				endforeach;
			} else {
				echo '<p>No cookie categories found. Default categories will be created when you save settings.</p>';
			}
			?>

			<!-- Submit button for main settings -->
			<?php submit_button( 'Save All Settings', 'primary', 'submit', false ); ?>
		</form>

		<!-- SEPARATE FORMS FOR ADDING COOKIES AND CATEGORIES -->
		<div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border: 1px solid #ddd;">
			<h3>Add New Category</h3>
			<form method="post" action="" id="warder-add-category-form">
				<?php wp_nonce_field( 'warder_add_category', 'warder_category_nonce' ); ?>
				<input type="text" name="new_category_id" placeholder="New category ID (e.g. marketing)" class="regular-text" required />
				<input type="submit" name="warder_add_category" value="Add New Category" class="button button-secondary" />
				<p class="description">Common categories: marketing, preferences, functional, etc.</p>
			</form>
		</div>

		<?php foreach ( $options['cookie_categories'] as $category_id => $category ) : ?>
		<div class="warder-add-cookie-form-container" style="margin: 10px 0; display: none;" id="warder-add-cookie-form-<?php echo esc_attr( $category_id ); ?>">
			<div style="padding: 15px; background: #f5f5f5; border: 1px solid #ddd;">
				<h4>Add Cookie to "<?php echo esc_html( $category['title'] ); ?>"</h4>
				<form method="post" action="" class="scc-add-cookie-form">
					<?php wp_nonce_field( 'warder_add_cookie', 'warder_cookie_nonce' ); ?>
					<input type="hidden" name="category_id" value="<?php echo esc_attr( $category_id ); ?>" />
					<table class="form-table">
						<tr>
							<th scope="row">Cookie Name/Pattern</th>
							<td>
								<input type="text" name="cookie_name" placeholder="e.g., _ga or /^_ga/" class="regular-text" required />
								<p class="description">Enter a specific cookie name or a pattern to match multiple cookies.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Match Type</th>
							<td>
								<label>
									<input type="checkbox" name="is_regex" />
									Regular Expression
								</label>
								<p class="description">Check if using a pattern like /^_ga/ to match multiple cookies.</p>
							</td>
						</tr>
					</table>

					<p>
						<input type="submit" name="warder_add_cookie" value="Add Cookie" class="button button-primary" />
						<button type="button" class="button button-secondary cancel-add-cookie">Cancel</button>
					</p>

					<h5>Common Cookie Patterns</h5>
					<ul class="cookie-pattern-examples">
						<li><strong>Google Analytics:</strong> <code>/^_ga/</code>, <code>_gid</code>, <code>_gat</code></li>
						<li><strong>Facebook:</strong> <code>/^_fb/</code>, <code>/^fb_/</code>, <code>_fbp</code></li>
						<li><strong>Google Ads:</strong> <code>_gcl_au</code>, <code>/^_gcl_/</code></li>
					</ul>
				</form>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		// Show/hide cookie add form.
		$('.show-add-cookie-form').on('click', function() {
			var categoryId = $(this).data('category');
			$('#warder-add-cookie-form-' + categoryId).show();
		});

		// Cancel button for cookie add form.
		$('.cancel-add-cookie').on('click', function(e) {
			e.preventDefault();
			$(this).closest('.warder-add-cookie-form-container').hide();
		});

		// Highlight changed fields.
		$('#warder-main-settings-form input, #warder-main-settings-form textarea, #warder-main-settings-form select').on('change', function() {
			$(this).css('background-color', '#ffffdd');
		});
	});
	</script>
	<?php
}

/**
 * Retrieves options from the database and deep-merges with defaults.
 *
 * @return array
 */
function warder_get_merged_options() {
	$options         = get_option( 'warder_options', array() );
	$default_options = warder_get_default_options();

	return wp_parse_args( $options, $default_options );
}

/**
 * Enqueues the bundled cookie consent script and localizes plugin settings.
 */
function warder_enqueue_scripts() {
	$options = warder_get_merged_options();
	if ( empty( $options['enabled'] ) ) {
		return;
	}

	$version = get_option( 'warder_options_last_updated', '1.0.0' );

	wp_enqueue_script(
		'warder-cookieconsent',
		plugin_dir_url( __FILE__ ) . 'dist/cookieconsent.bundle.js',
		array(),
		$version,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_localize_script(
		'warder-cookieconsent',
		'warderSettings',
		array(
			'settings' => $options,
			'version'  => $version,
		)
	);

	if ( ! empty( $options['show_preferences_toggle'] ) ) {
		wp_register_style( 'warder-preferences-toggle', false, array(), $version ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'warder-preferences-toggle' );
		wp_add_inline_style( 'warder-preferences-toggle', warder_get_preferences_toggle_css() );
	}
}
add_action( 'wp_enqueue_scripts', 'warder_enqueue_scripts' );

add_action( 'update_option_warder_options', 'warder_update_options_timestamp', 10, 2 );
/**
 * Updates the options timestamp whenever the plugin settings are saved.
 *
 * @param mixed $old_value Previous option value (unused).
 * @param mixed $new_value New option value (unused).
 */
function warder_update_options_timestamp( $old_value, $new_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	update_option( 'warder_options_last_updated', time() );
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

	if ( function_exists( 'get_plugin_data' ) ) {
		$plugin_data = get_plugin_data( __FILE__ );
		$plugin_name = $plugin_data['Name'];
	} else {
		$plugin_name = 'Warder Cookie Consent';
	}

	echo '<div class="notice notice-info is-dismissible">';
	echo '<p>' . sprintf(
		/* translators: 1: Plugin name, 2: HTML link to settings page. */
		esc_html__( 'Thank you for installing %1$s! Please configure your settings on the %2$s.', 'warder-cookie-consent' ),
		esc_html( $plugin_name ),
		'<a href="' . esc_url( admin_url( 'options-general.php?page=warder-cookie-consent' ) ) . '">settings page</a>'
	) . '</p>';
	echo '</div>';
}
add_action( 'admin_notices', 'warder_admin_notices' );

register_activation_hook( __FILE__, 'warder_plugin_activate' );
/**
 * Merges existing options with defaults on plugin activation to preserve user data.
 */
function warder_plugin_activate() {
	$options         = get_option( 'warder_options', array() );
	$default_options = warder_get_default_options();

	$merged_options = wp_parse_args( $options, $default_options );

	update_option( 'warder_options', $merged_options );
}

/**
 * Returns CSS for the floating preferences toggle button.
 *
 * @return string
 */
function warder_get_preferences_toggle_css() {
	return '
.warder-preferences-toggle {
	position: fixed;
	width: 48px;
	height: 48px;
	border-radius: 50%;
	background: #333;
	color: #fff;
	border: none;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
	z-index: 9999;
	transition: background 0.2s ease, transform 0.2s ease;
}
.warder-preferences-toggle:hover {
	background: #555;
	transform: scale(1.1);
}
.warder-preferences-toggle svg {
	width: 24px;
	height: 24px;
	pointer-events: none;
}
.warder-preferences-toggle--bottom-right { bottom: 20px; right: 20px; }
.warder-preferences-toggle--bottom-left  { bottom: 20px; left: 20px; }
.warder-preferences-toggle--top-right    { top: 20px; right: 20px; }
.warder-preferences-toggle--top-left     { top: 20px; left: 20px; }
';
}

/**
 * Outputs the floating preferences toggle button in the footer.
 */
function warder_add_preferences_button() {
	$options = warder_get_merged_options();
	if ( empty( $options['enabled'] ) || empty( $options['show_preferences_toggle'] ) ) {
		return;
	}

	$allowed  = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
	$position = isset( $options['preferences_toggle_position'] ) && in_array( $options['preferences_toggle_position'], $allowed, true )
		? $options['preferences_toggle_position']
		: 'bottom-right';

	echo '<button id="warder-preferences-toggle" class="warder-preferences-toggle warder-preferences-toggle--' . esc_attr( $position ) . '" aria-label="' . esc_attr__( 'Cookie Preferences', 'warder-cookie-consent' ) . '">';
	echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
	echo '<circle cx="12" cy="12" r="10"/>';
	echo '<circle cx="9" cy="9" r="1.5" fill="currentColor"/>';
	echo '<circle cx="15" cy="8" r="1" fill="currentColor"/>';
	echo '<circle cx="14" cy="14" r="1.5" fill="currentColor"/>';
	echo '<circle cx="9" cy="15" r="1" fill="currentColor"/>';
	echo '</svg>';
	echo '</button>';
}
add_action( 'wp_footer', 'warder_add_preferences_button' );

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
	<p class="description">The name displayed to users in the consent preferences panel.</p>
	<?php
}
