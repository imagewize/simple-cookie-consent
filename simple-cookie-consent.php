<?php
/**
 * Plugin Name: Simple Cookie Consent
 * Description: Implements GDPR-compliant cookie consent functionality.
 * Version: 1.0.0
 * Author: Jasper Frumau
 */

// Enqueue scripts and styles
function scc_enqueue_scripts() {
    // Enqueue the bundled JavaScript file
    wp_enqueue_script( 'scc-cookieconsent', plugin_dir_url( __FILE__ ) . 'dist/cookieconsent.bundle.js', array(), '1.0.0', true );

    // Enqueue the cookieconsent CSS file
    wp_enqueue_style( 'scc-cookieconsent-style', plugin_dir_url( __FILE__ ) . 'dist/cookieconsent.css', array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'scc_enqueue_scripts' );

// Add admin notices for configuration
function scc_admin_notices() {
    // Check if the necessary functions exist before using them
    if ( function_exists( 'get_plugin_data' ) ) {
        $plugin_data = get_plugin_data( __FILE__ );
        $plugin_name = $plugin_data['Name'];
    } else {
        $plugin_name = 'Simple Cookie Consent'; // Fallback name
    }
    
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>' . esc_html( sprintf( 'Thank you for installing %s! Please configure the cookie consent settings in src/index.js and rebuild the plugin.', $plugin_name ) ) . '</p>';
    echo '</div>';
}
add_action( 'admin_notices', 'scc_admin_notices' );
