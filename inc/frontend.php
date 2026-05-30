<?php
/**
 * Frontend script enqueueing and preferences toggle button.
 *
 * @package Warder_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

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
		plugin_dir_url( WARDER_PLUGIN_FILE ) . 'dist/cookieconsent.bundle.js',
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
		// warder_get_preferences_toggle_css() returns static, hardcoded CSS with no
		// user input, so it is output as-is. wp_strip_all_tags() is intentionally
		// not used here: it is an HTML helper and the wrong tool for CSS content.
		wp_add_inline_style( 'warder-preferences-toggle', warder_get_preferences_toggle_css() );
	}
}
add_action( 'wp_enqueue_scripts', 'warder_enqueue_scripts' );

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
