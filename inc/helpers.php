<?php
/**
 * Public helper functions for theme and plugin authors.
 *
 * @package Warder_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'warder_has_consent' ) ) {
	/**
	 * Check whether the current visitor has consented to a given cookie category.
	 *
	 * Reads the `cc_cookie` set by vanilla-cookieconsent v3, whose payload stores
	 * accepted categories as a string array (e.g. `["necessary","analytics"]`).
	 * The `necessary` category is always treated as granted.
	 *
	 * @param string $category Category ID (e.g. 'analytics', 'marketing').
	 * @return bool True if the category is accepted.
	 */
	function warder_has_consent( $category ) {
		$category = sanitize_key( $category );

		if ( '' === $category ) {
			return false;
		}

		if ( 'necessary' === $category ) {
			return true;
		}

		if ( empty( $_COOKIE['cc_cookie'] ) ) {
			return false;
		}

		$raw  = wp_unslash( $_COOKIE['cc_cookie'] );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || empty( $data['categories'] ) || ! is_array( $data['categories'] ) ) {
			return false;
		}

		return in_array( $category, $data['categories'], true );
	}
}
