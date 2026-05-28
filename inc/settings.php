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
