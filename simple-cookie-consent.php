<?php
/**
 * Plugin Name: Simple Cookie Consent
 * Description: Implements GDPR-compliant cookie consent functionality.
 * Version: 1.0.0
 * Author: Jasper Frumau
 */

// Register plugin settings
function scc_register_settings() {
    register_setting('scc_options_group', 'scc_options', 'scc_validate_options');
    
    // Add default options on activation
    if (false === get_option('scc_options')) {
        add_option('scc_options', scc_get_default_options());
    }
}
add_action('admin_init', 'scc_register_settings');

// Default options
function scc_get_default_options() {
    return array(
        'current_lang' => 'en',
        'autoclear_cookies' => true,
        'page_scripts' => true,
        'title' => 'We use cookies!',
        'description' => 'Hello, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent.',
        'primary_btn_text' => 'Accept all',
        'primary_btn_role' => 'accept_all',
        'secondary_btn_text' => 'Reject all',
        'secondary_btn_role' => 'accept_necessary',
        'privacy_policy_url' => '#privacy-policy',
    );
}

// Validate options
function scc_validate_options($input) {
    $valid = array();
    
    $valid['current_lang'] = sanitize_text_field($input['current_lang']);
    $valid['autoclear_cookies'] = isset($input['autoclear_cookies']) ? true : false;
    $valid['page_scripts'] = isset($input['page_scripts']) ? true : false;
    $valid['title'] = sanitize_text_field($input['title']);
    $valid['description'] = wp_kses_post($input['description']);
    $valid['primary_btn_text'] = sanitize_text_field($input['primary_btn_text']);
    $valid['primary_btn_role'] = in_array($input['primary_btn_role'], array('accept_all', 'accept_selected')) 
        ? $input['primary_btn_role'] : 'accept_all';
    $valid['secondary_btn_text'] = sanitize_text_field($input['secondary_btn_text']);
    $valid['secondary_btn_role'] = in_array($input['secondary_btn_role'], array('accept_necessary', 'settings')) 
        ? $input['secondary_btn_role'] : 'accept_necessary';
    $valid['privacy_policy_url'] = sanitize_text_field($input['privacy_policy_url']);
    
    return $valid;
}

// Add options page to menu
function scc_add_options_page() {
    add_options_page(
        'Cookie Consent Settings',
        'Cookie Consent',
        'manage_options',
        'simple-cookie-consent',
        'scc_render_options_page'
    );
}
add_action('admin_menu', 'scc_add_options_page');

// Render the options page
function scc_render_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $options = get_option('scc_options', scc_get_default_options());
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('scc_options_group'); ?>
            
            <h2>General Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Language</th>
                    <td>
                        <input type="text" name="scc_options[current_lang]" value="<?php echo esc_attr($options['current_lang']); ?>" />
                        <p class="description">Default: en</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto-clear Cookies</th>
                    <td>
                        <label>
                            <input type="checkbox" name="scc_options[autoclear_cookies]" <?php checked($options['autoclear_cookies'], true); ?> />
                            Automatically clear cookies when user rejects them
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Page Scripts</th>
                    <td>
                        <label>
                            <input type="checkbox" name="scc_options[page_scripts]" <?php checked($options['page_scripts'], true); ?> />
                            Control script execution based on user consent
                        </label>
                    </td>
                </tr>
            </table>
            
            <h2>Consent Modal</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Title</th>
                    <td>
                        <input type="text" name="scc_options[title]" value="<?php echo esc_attr($options['title']); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Description</th>
                    <td>
                        <textarea name="scc_options[description]" rows="4" class="large-text"><?php echo esc_textarea($options['description']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Primary Button</th>
                    <td>
                        <input type="text" name="scc_options[primary_btn_text]" value="<?php echo esc_attr($options['primary_btn_text']); ?>" class="regular-text" />
                        <select name="scc_options[primary_btn_role]">
                            <option value="accept_all" <?php selected($options['primary_btn_role'], 'accept_all'); ?>>Accept All</option>
                            <option value="accept_selected" <?php selected($options['primary_btn_role'], 'accept_selected'); ?>>Accept Selected</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Secondary Button</th>
                    <td>
                        <input type="text" name="scc_options[secondary_btn_text]" value="<?php echo esc_attr($options['secondary_btn_text']); ?>" class="regular-text" />
                        <select name="scc_options[secondary_btn_role]">
                            <option value="accept_necessary" <?php selected($options['secondary_btn_role'], 'accept_necessary'); ?>>Accept Necessary</option>
                            <option value="settings" <?php selected($options['secondary_btn_role'], 'settings'); ?>>Settings</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Privacy Policy URL</th>
                    <td>
                        <input type="text" name="scc_options[privacy_policy_url]" value="<?php echo esc_attr($options['privacy_policy_url']); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// Enqueue scripts and styles
function scc_enqueue_scripts() {
    // Enqueue the bundled JavaScript file
    wp_enqueue_script('scc-cookieconsent', plugin_dir_url(__FILE__) . 'dist/cookieconsent.bundle.js', array(), '1.0.0', true);
    
    // Get options from database or use defaults
    $options = get_option('scc_options', scc_get_default_options());
    
    // FIXED: Added version number to avoid caching issues
    wp_localize_script('scc-cookieconsent', 'sccSettings', array(
        'settings' => $options,
        // Add debug timestamp to detect if script is properly loaded
        'version' => time() 
    ));
    
    // FIXED: Added console debug to check if script is loaded
    echo "<!-- Simple Cookie Consent plugin loaded -->\n";
}
add_action('wp_enqueue_scripts', 'scc_enqueue_scripts');

// Add admin notices for configuration
function scc_admin_notices() {
    global $pagenow;
    
    // Don't show notice on the settings page
    if ($pagenow === 'options-general.php' && isset($_GET['page']) && $_GET['page'] === 'simple-cookie-consent') {
        return;
    }
    
    // Check if the necessary functions exist before using them
    if (function_exists('get_plugin_data')) {
        $plugin_data = get_plugin_data(__FILE__);
        $plugin_name = $plugin_data['Name'];
    } else {
        $plugin_name = 'Simple Cookie Consent'; // Fallback name
    }
    
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p>' . sprintf(
        esc_html__('Thank you for installing %s! Please configure your settings on the %s.', 'simple-cookie-consent'),
        esc_html($plugin_name),
        '<a href="' . esc_url(admin_url('options-general.php?page=simple-cookie-consent')) . '">settings page</a>'
    ) . '</p>';
    echo '</div>';
}
add_action('admin_notices', 'scc_admin_notices');

// FIXED: Add activation hook to ensure default options are set
register_activation_hook(__FILE__, 'scc_plugin_activate');
function scc_plugin_activate() {
    // Ensure default options are set on plugin activation
    if (false === get_option('scc_options')) {
        add_option('scc_options', scc_get_default_options());
    }
}