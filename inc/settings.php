<?php
/**
 * Settings registration, validation, timestamp updates, and plugin activation.
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
 * Recursively sanitizes raw settings input from a form submission.
 *
 * Walks the nested options array and sanitizes every scalar leaf. Values under
 * a 'description' key are run through wp_kses_post() because the consent banner
 * renders them as markup on the front end (e.g. a privacy-policy link); every
 * other value is treated as plain text. Structural validation, type coercion,
 * and whitelisting happen afterwards in warder_validate_options().
 *
 * @param mixed  $value Raw value (array or scalar) from the request.
 * @param string $key   The array key for the current value, used for context.
 * @return mixed Sanitized value of the same shape as the input.
 */
function warder_sanitize_options_input( $value, $key = '' ) {
	if ( is_array( $value ) ) {
		$clean = array();
		foreach ( $value as $k => $v ) {
			$clean[ $k ] = warder_sanitize_options_input( $v, (string) $k );
		}
		return $clean;
	}

	if ( 'description' === $key ) {
		return wp_kses_post( $value );
	}

	return sanitize_text_field( $value );
}

/**
 * Sanitizes and validates options before saving to the database.
 *
 * Every field is guarded with isset() so a partial form submission cannot
 * trigger PHP warnings, and free-text fields are sanitized while
 * choice fields are constrained to a known whitelist.
 *
 * @param array $input Raw input from the settings form.
 * @return array Sanitized options.
 */
function warder_validate_options( $input ) {
	$valid = array();

	$valid['enabled']                     = isset( $input['enabled'] ) ? true : false;
	$valid['current_lang']                = isset( $input['current_lang'] ) && in_array( $input['current_lang'], array( 'en', 'fr', 'de', 'es', 'it', 'nl' ), true )
		? $input['current_lang'] : 'en';
	$valid['autoclear_cookies']           = isset( $input['autoclear_cookies'] ) ? true : false;
	$valid['page_scripts']                = isset( $input['page_scripts'] ) ? true : false;
	$valid['title']                       = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';
	$valid['description']                 = isset( $input['description'] ) ? wp_kses_post( $input['description'] ) : '';
	$valid['primary_btn_text']            = isset( $input['primary_btn_text'] ) ? sanitize_text_field( $input['primary_btn_text'] ) : '';
	$valid['primary_btn_role']            = isset( $input['primary_btn_role'] ) && in_array( $input['primary_btn_role'], array( 'accept_all', 'accept_selected' ), true )
		? $input['primary_btn_role'] : 'accept_all';
	$valid['secondary_btn_text']          = isset( $input['secondary_btn_text'] ) ? sanitize_text_field( $input['secondary_btn_text'] ) : '';
	$valid['secondary_btn_role']          = isset( $input['secondary_btn_role'] ) && in_array( $input['secondary_btn_role'], array( 'accept_necessary', 'settings' ), true )
		? $input['secondary_btn_role'] : 'accept_necessary';
	$valid['privacy_policy_url']          = isset( $input['privacy_policy_url'] ) ? esc_url_raw( $input['privacy_policy_url'] ) : '';
	$valid['show_preferences_toggle']     = isset( $input['show_preferences_toggle'] ) ? true : false;
	$valid['preferences_toggle_position'] = isset( $input['preferences_toggle_position'] ) && in_array( $input['preferences_toggle_position'], array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ), true )
		? $input['preferences_toggle_position'] : 'bottom-right';

	if ( isset( $input['cookie_categories'] ) && is_array( $input['cookie_categories'] ) ) {
		$valid['cookie_categories'] = array();

		foreach ( $input['cookie_categories'] as $category_id => $category ) {
			$sanitized_id = sanitize_key( $category_id );

			$title = isset( $category['title'] ) ? sanitize_text_field( $category['title'] ) : '';

			// The 'necessary' category is always enabled and read-only regardless of form input,
			// because its checkboxes are disabled in the admin and therefore not submitted.
			$is_necessary = ( 'necessary' === $sanitized_id );

			$valid['cookie_categories'][ $sanitized_id ] = array(
				'title'       => $title,
				'description' => wp_kses_post( $category['description'] ),
				'enabled'     => $is_necessary,
				'readonly'    => $is_necessary,
				'cookies'     => array(),
			);

			if ( isset( $category['cookies'] ) && is_array( $category['cookies'] ) ) {
				foreach ( $category['cookies'] as $cookie ) {
					if ( ! empty( $cookie['name'] ) ) {
						$valid['cookie_categories'][ $sanitized_id ]['cookies'][] = array(
							'name'     => sanitize_text_field( $cookie['name'] ),
							'is_regex' => ! empty( $cookie['is_regex'] ) && '0' !== (string) $cookie['is_regex'],
						);
					}
				}
			}
		}
	}

	return $valid;
}

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

register_activation_hook( WARDER_PLUGIN_FILE, 'warder_plugin_activate' );
/**
 * Merges existing options with defaults on plugin activation to preserve user data.
 */
function warder_plugin_activate() {
	$options         = get_option( 'warder_options', array() );
	$default_options = warder_get_default_options();

	$merged_options = wp_parse_args( $options, $default_options );

	update_option( 'warder_options', $merged_options );
}
