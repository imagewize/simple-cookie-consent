<?php
/**
 * Plugin Name: Warder Cookie Consent
 * Description: GDPR-compliant cookie consent banner with category management and floating preferences toggle.
 * Version: 2.0.2
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

define( 'WARDER_VERSION', '2.0.2' );
define( 'WARDER_PLUGIN_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'inc/defaults.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/admin.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/frontend.php';
