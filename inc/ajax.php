<?php
/**
 * AJAX handlers for settings save and category/cookie management.
 *
 * @package Warder_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX save of plugin settings from the admin settings page.
 */
function warder_ajax_save_settings() {
	check_ajax_referer( 'warder_options_group-options', '_wpnonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'warder-cookie-consent' ) ) );
	}

	$input = isset( $_POST['warder_options'] ) && is_array( $_POST['warder_options'] )
		? warder_sanitize_options_input( wp_unslash( $_POST['warder_options'] ) )
		: array();
	$valid = warder_validate_options( $input );

	update_option( 'warder_options', $valid );
	delete_transient( 'warder_options_cache' );
	wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'warder-cookie-consent' ) ) );
}
add_action( 'wp_ajax_warder_save_settings', 'warder_ajax_save_settings' );

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
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'warder-cookie-consent',
					'warder_notice' => 'saved',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	return $options;
}
