<?php
/**
 * Default options and merged options retrieval.
 *
 * @package Warder_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

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
					// Consent record — must always be kept.
					array(
						'name'     => 'cc_cookie',
						'is_regex' => false,
					),
					// WordPress login & security.
					array(
						'name'     => '/^wordpress_logged_in_/',
						'is_regex' => true,
					),
					array(
						'name'     => '/^wordpress_sec_/',
						'is_regex' => true,
					),
					array(
						'name'     => 'wordpress_test_cookie',
						'is_regex' => false,
					),
					// WordPress admin bar / customizer.
					array(
						'name'     => '/^wp-settings-/',
						'is_regex' => true,
					),
					// WooCommerce session & cart.
					array(
						'name'     => '/^wp_woocommerce_session_/',
						'is_regex' => true,
					),
					array(
						'name'     => 'woocommerce_cart_hash',
						'is_regex' => false,
					),
					array(
						'name'     => 'woocommerce_items_in_cart',
						'is_regex' => false,
					),
					array(
						'name'     => 'woocommerce_recently_viewed',
						'is_regex' => false,
					),
					// PHP session.
					array(
						'name'     => 'PHPSESSID',
						'is_regex' => false,
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
					array(
						'name'     => '/^sbjs_/',
						'is_regex' => true,
					),
				),
			),
		),
	);
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
 * Returns the supported banner languages as a code => label map.
 *
 * Single source of truth for both validation (the keys) and the admin
 * language <select> (the labels). Adding a language here exposes it everywhere.
 *
 * @return array<string,string>
 */
function warder_allowed_languages() {
	return array(
		'en' => __( 'English', 'warder-cookie-consent' ),
		'fr' => __( 'French', 'warder-cookie-consent' ),
		'de' => __( 'German', 'warder-cookie-consent' ),
		'es' => __( 'Spanish', 'warder-cookie-consent' ),
		'it' => __( 'Italian', 'warder-cookie-consent' ),
		'nl' => __( 'Dutch', 'warder-cookie-consent' ),
	);
}

/**
 * Returns the allowed preferences-toggle positions as a value => label map.
 *
 * Single source of truth for validation (the keys), the frontend position
 * guard, and the admin position <select> (the labels).
 *
 * @return array<string,string>
 */
function warder_allowed_toggle_positions() {
	return array(
		'bottom-right' => __( 'Bottom Right', 'warder-cookie-consent' ),
		'bottom-left'  => __( 'Bottom Left', 'warder-cookie-consent' ),
		'top-right'    => __( 'Top Right', 'warder-cookie-consent' ),
		'top-left'     => __( 'Top Left', 'warder-cookie-consent' ),
	);
}
