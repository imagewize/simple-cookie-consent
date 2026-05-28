<?php
/**
 * Plugin Name: Warder Cookie Consent
 * Description: GDPR-compliant cookie consent banner with category management and floating preferences toggle.
 * Version: 1.5.1
 * Author: Jasper Frumau
 * Author URI: https://imagewize.com
 * Requires at least: 5.0
 * Requires PHP: 8.0
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
	register_setting(
		'warder_options_group',
		'warder_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'warder_validate_options',
		)
	);

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
					array(
						'name'     => '/^_pk_/',
						'is_regex' => true,
					),
					array(
						'name'     => '/^mtm_/',
						'is_regex' => true,
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
	$valid['privacy_policy_url']          = esc_url_raw( $input['privacy_policy_url'] );
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
 * Enqueues jQuery-dependent admin scripts for the plugin settings page.
 *
 * @param string $hook The current admin page hook suffix.
 */
function warder_enqueue_admin_scripts( $hook ) {
	if ( 'settings_page_warder-cookie-consent' !== $hook ) {
		return;
	}

	wp_enqueue_script( 'jquery' );

	$admin_js = '
jQuery(document).ready(function($) {
	$(".show-add-cookie-form").on("click", function() {
		var categoryId = $(this).data("category");
		$("#warder-add-cookie-container-" + categoryId).show();
	});
	$(".cancel-add-cookie").on("click", function(e) {
		e.preventDefault();
		$(this).closest(".warder-add-cookie-form-container").hide();
	});
	$("#warder-main-settings-form input, #warder-main-settings-form textarea, #warder-main-settings-form select").on("change", function() {
		$(this).css("background-color", "#ffffdd");
	});
});';

	wp_add_inline_script( 'jquery', $admin_js );
}
add_action( 'admin_enqueue_scripts', 'warder_enqueue_admin_scripts' );

/**
 * Processes add/delete actions for cookie categories and cookies on the settings page.
 *
 * @param array $options The current merged plugin options.
 * @return array The options after any add/delete action has been applied.
 */
function warder_handle_admin_actions( $options ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $options;
	}

	$changed = false;

	// Add a new cookie category.
	if ( isset( $_POST['warder_add_category'], $_POST['warder_category_nonce'] ) ) {
		check_admin_referer( 'warder_add_category', 'warder_category_nonce' );

		$new_id = isset( $_POST['new_category_id'] ) ? sanitize_key( wp_unslash( $_POST['new_category_id'] ) ) : '';

		if ( '' !== $new_id && ! isset( $options['cookie_categories'][ $new_id ] ) ) {
			$options['cookie_categories'][ $new_id ] = array(
				'title'       => ucfirst( $new_id ),
				'description' => '',
				'enabled'     => false,
				'readonly'    => false,
				'cookies'     => array(),
			);
			$changed                                 = true;
		}
	}

	// Add a cookie to an existing category.
	if ( isset( $_POST['warder_add_cookie'], $_POST['warder_cookie_nonce'] ) ) {
		check_admin_referer( 'warder_add_cookie', 'warder_cookie_nonce' );

		$category_id = isset( $_POST['category_id'] ) ? sanitize_key( wp_unslash( $_POST['category_id'] ) ) : '';
		$cookie_name = isset( $_POST['cookie_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cookie_name'] ) ) : '';
		$is_regex    = isset( $_POST['is_regex'] );

		if ( '' !== $cookie_name && isset( $options['cookie_categories'][ $category_id ] ) ) {
			$options['cookie_categories'][ $category_id ]['cookies'][] = array(
				'name'     => $cookie_name,
				'is_regex' => $is_regex,
			);
			$changed = true;
		}
	}

	// Delete a cookie category.
	if ( isset( $_GET['action'] ) && 'delete_category' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		$category_id = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : '';
		$nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( wp_verify_nonce( $nonce, 'delete_category_' . $category_id ) && 'necessary' !== $category_id && isset( $options['cookie_categories'][ $category_id ] ) ) {
			unset( $options['cookie_categories'][ $category_id ] );
			$changed = true;
		}
	}

	// Delete a single cookie from a category.
	if ( isset( $_GET['action'] ) && 'delete_cookie' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		$category_id  = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : '';
		$cookie_index = isset( $_GET['cookie_index'] ) ? absint( wp_unslash( $_GET['cookie_index'] ) ) : -1;
		$nonce        = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( $cookie_index >= 0 && wp_verify_nonce( $nonce, 'delete_cookie_' . $category_id . '_' . $cookie_index ) && isset( $options['cookie_categories'][ $category_id ]['cookies'][ $cookie_index ] ) ) {
			array_splice( $options['cookie_categories'][ $category_id ]['cookies'], $cookie_index, 1 );
			$changed = true;
		}
	}

	if ( $changed ) {
		update_option( 'warder_options', $options );
		delete_transient( 'warder_options_cache' );
	}

	return $options;
}

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

	$options = warder_handle_admin_actions( $options );

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( $settings_updated ) : ?>
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
										<?php checked( $category['enabled'], true ); ?> />
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

					<!-- Add Cookie Form Container (the actual <form> lives outside the main settings form;
						 inputs reference it via the HTML5 `form` attribute so they aren't nested). -->
					<div class="warder-add-cookie-form-container" style="margin: 10px 0; display: none;" id="warder-add-cookie-container-<?php echo esc_attr( $category_id ); ?>">
						<div style="padding: 15px; background: #f5f5f5; border: 1px solid #ddd;">
							<h4>
								<?php
								/* translators: %s: cookie category title. */
								printf( esc_html__( 'Add Cookie to "%s"', 'warder-cookie-consent' ), esc_html( $category['title'] ) );
								?>
							</h4>
							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Cookie Name/Pattern', 'warder-cookie-consent' ); ?></th>
									<td>
										<input type="text" name="cookie_name" form="warder-add-cookie-form-<?php echo esc_attr( $category_id ); ?>" placeholder="<?php esc_attr_e( 'e.g., _ga or /^_ga/', 'warder-cookie-consent' ); ?>" class="regular-text" required />
										<p class="description"><?php esc_html_e( 'Enter a specific cookie name or a pattern to match multiple cookies.', 'warder-cookie-consent' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Match Type', 'warder-cookie-consent' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="is_regex" form="warder-add-cookie-form-<?php echo esc_attr( $category_id ); ?>" />
											<?php esc_html_e( 'Regular Expression', 'warder-cookie-consent' ); ?>
										</label>
										<p class="description"><?php esc_html_e( 'Check if using a pattern like /^_ga/ to match multiple cookies.', 'warder-cookie-consent' ); ?></p>
									</td>
								</tr>
							</table>

							<p>
								<input type="submit" name="warder_add_cookie" form="warder-add-cookie-form-<?php echo esc_attr( $category_id ); ?>" value="<?php esc_attr_e( 'Add Cookie', 'warder-cookie-consent' ); ?>" class="button button-primary" />
								<button type="button" class="button button-secondary cancel-add-cookie"><?php esc_html_e( 'Cancel', 'warder-cookie-consent' ); ?></button>
							</p>

							<h5><?php esc_html_e( 'Common Cookie Patterns', 'warder-cookie-consent' ); ?></h5>
							<ul class="cookie-pattern-examples">
								<li><strong>Google Analytics:</strong> <code>/^_ga/</code>, <code>_gid</code>, <code>_gat</code></li>
								<li><strong>Facebook:</strong> <code>/^_fb/</code>, <code>/^fb_/</code>, <code>_fbp</code></li>
								<li><strong>Google Ads:</strong> <code>_gcl_au</code>, <code>/^_gcl_/</code></li>
								<li><strong>Matomo:</strong> <code>/^_pk_/</code>, <code>/^mtm_/</code></li>
							</ul>
						</div>
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
		// Out-of-DOM Add Cookie forms — one per category. The visible inputs above use
		// form="warder-add-cookie-form-<id>" to submit here, avoiding nested forms.
		if ( isset( $options['cookie_categories'] ) && is_array( $options['cookie_categories'] ) ) :
			foreach ( array_keys( $options['cookie_categories'] ) as $form_category_id ) :
				?>
				<form method="post" action="" id="warder-add-cookie-form-<?php echo esc_attr( $form_category_id ); ?>" style="display:none;">
					<?php wp_nonce_field( 'warder_add_cookie', 'warder_cookie_nonce' ); ?>
					<input type="hidden" name="category_id" value="<?php echo esc_attr( $form_category_id ); ?>" />
				</form>
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
		wp_add_inline_style( 'warder-preferences-toggle', wp_strip_all_tags( warder_get_preferences_toggle_css() ) );
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
		'<a href="' . esc_url( admin_url( 'options-general.php?page=warder-cookie-consent' ) ) . '">' . esc_html__( 'settings page', 'warder-cookie-consent' ) . '</a>'
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
	<p class="description"><?php esc_html_e( 'The name displayed to users in the consent preferences panel.', 'warder-cookie-consent' ); ?></p>
	<?php
}
