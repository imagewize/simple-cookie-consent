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
 * Retrieves options from the database and deep-merges with defaults.
 *
 * @return array
 */
function warder_get_merged_options() {
	$options         = get_option( 'warder_options', array() );
	$default_options = warder_get_default_options();

	return wp_parse_args( $options, $default_options );
}
