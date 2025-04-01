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
        'cookie_categories' => array(
            'necessary' => array(
                'title' => 'Strictly Necessary',
                'description' => 'These cookies are essential for the proper functioning of the website and cannot be disabled.',
                'enabled' => true,
                'readonly' => true,
                'cookies' => array()
            ),
            'analytics' => array(
                'title' => 'Performance and Analytics',
                'description' => 'These cookies collect information about how you use our website. All of the data is anonymized and cannot be used to identify you.',
                'enabled' => false,
                'readonly' => false,
                'cookies' => array(
                    array('name' => '/^_ga/', 'is_regex' => true),
                    array('name' => '_gid', 'is_regex' => false),
                    array('name' => '_gat', 'is_regex' => false)
                )
            )
        )
    );
}

// Validate options
function scc_validate_options($input) {
    // Debug input
    error_log('Validating options: ' . print_r($input, true));
    
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
    
    // Validate cookie categories
    if (isset($input['cookie_categories']) && is_array($input['cookie_categories'])) {
        $valid['cookie_categories'] = array();
        
        foreach ($input['cookie_categories'] as $category_id => $category) {
            $sanitized_id = sanitize_key($category_id);
            
            // Debug each category's data
            error_log("Processing category: $sanitized_id - " . print_r($category, true));
            
            // Make sure title is properly processed
            $title = isset($category['title']) ? sanitize_text_field($category['title']) : '';
            error_log("Category title: $title");
            
            $valid['cookie_categories'][$sanitized_id] = array(
                'title' => $title,
                'description' => wp_kses_post($category['description']),
                'enabled' => isset($category['enabled']) ? true : false,
                'readonly' => isset($category['readonly']) ? true : false,
                'cookies' => array()
            );
            
            // Process cookies for this category
            if (isset($category['cookies']) && is_array($category['cookies'])) {
                foreach ($category['cookies'] as $cookie) {
                    if (!empty($cookie['name'])) {
                        $valid['cookie_categories'][$sanitized_id]['cookies'][] = array(
                            'name' => sanitize_text_field($cookie['name']),
                            'is_regex' => isset($cookie['is_regex']) ? true : false
                        );
                    }
                }
            }
        }
    }
    
    // Debug output
    error_log('Validated options: ' . print_r($valid, true));
    
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
    
    // Check if settings were updated
    $settings_updated = false;
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
        $settings_updated = true;
        delete_transient('scc_options_cache');
    }
    
    // Get options with defaults
    $options = get_option('scc_options', array());
    $default_options = scc_get_default_options();
    $options = wp_parse_args($options, $default_options);
    
    // Ensure cookie_categories exists and is an array
    if (!isset($options['cookie_categories']) || !is_array($options['cookie_categories'])) {
        $options['cookie_categories'] = $default_options['cookie_categories'];
    }
    
    // Process cookie category and cookie actions with a separate form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle category & cookie additions separately from the main settings form
        // ...existing code for handling POST requests...
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if ($settings_updated): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Settings saved successfully.</strong></p>
        </div>
        <?php endif; ?>
        
        <!-- MAIN SETTINGS FORM -->
        <form method="post" action="options.php" id="scc-main-settings-form">
            <?php 
            // This is critical - it adds the proper nonce fields
            settings_fields('scc_options_group');
            ?>
            
            <!-- General Settings Section -->
            <h2>General Settings</h2>
            <table class="form-table">
                <!-- ...existing general settings fields... -->
            </table>
            
            <!-- Consent Modal Section -->
            <h2>Consent Modal</h2>
            <table class="form-table">
                <!-- ...existing consent modal fields... -->
            </table>
            
            <!-- Cookie Categories Section -->
            <h2>Cookie Categories</h2>
            <p>Configure cookie categories and specific cookies to be blocked until consent is given.</p>
            
            <?php
            // Display cookie categories
            if (isset($options['cookie_categories']) && is_array($options['cookie_categories'])) {
                foreach ($options['cookie_categories'] as $category_id => $category) : 
            ?>
                <div class="scc-category-section" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                    <h3 style="margin-top: 0;"><?php echo esc_html($category['title']); ?> (<?php echo esc_html($category_id); ?>)</h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Title</th>
                            <td>
                                <input type="text" 
                                       name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][title]" 
                                       value="<?php echo esc_attr($category['title']); ?>" 
                                       class="regular-text scc-category-title-field" 
                                       id="scc-category-<?php echo esc_attr($category_id); ?>-title" />
                                <p class="description">The name displayed to users in the consent preferences panel.</p>
                            </td>
                        </tr>
                        <!-- ...other category fields... -->
                    </table>
                    
                    <!-- Cookies table... -->
                    <?php if (!empty($category['cookies'])) : ?>
                        <!-- ...existing cookies table... -->
                    <?php else : ?>
                        <p>No cookies defined for this category yet.</p>
                    <?php endif; ?>
                    
                    <!-- Add cookie form - must be outside the main form -->
                </div>
            <?php 
                endforeach;
            } else {
                echo '<p>No cookie categories found. Default categories will be created when you save settings.</p>';
            }
            ?>
            
            <!-- Submit button for main settings -->
            <?php submit_button('Save All Settings', 'primary', 'submit', false); ?>
        </form>
        
        <!-- SEPARATE FORMS FOR ADDING COOKIES AND CATEGORIES -->
        <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border: 1px solid #ddd;">
            <h3>Add New Category</h3>
            <form method="post" action="" id="scc-add-category-form">
                <?php wp_nonce_field('scc_add_category', 'scc_category_nonce'); ?>
                <input type="text" name="new_category_id" placeholder="New category ID (e.g. marketing)" class="regular-text" required />
                <input type="submit" name="scc_add_category" value="Add New Category" class="button button-secondary" />
            </form>
        </div>
        
        <?php foreach ($options['cookie_categories'] as $category_id => $category) : ?>
        <div style="margin: 10px 0; display: none;" id="scc-add-cookie-form-<?php echo esc_attr($category_id); ?>">
            <h4>Add Cookie to <?php echo esc_html($category['title']); ?></h4>
            <form method="post" action="" class="scc-add-cookie-form">
                <?php wp_nonce_field('scc_add_cookie', 'scc_cookie_nonce'); ?>
                <input type="hidden" name="category_id" value="<?php echo esc_attr($category_id); ?>" />
                <input type="text" name="cookie_name" placeholder="Cookie name or pattern (e.g. _ga or /^_ga/)" class="regular-text" required />
                <label>
                    <input type="checkbox" name="is_regex" />
                    Regular Expression
                </label>
                <input type="submit" name="scc_add_cookie" value="Add Cookie" class="button button-secondary" />
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Debug form submission
        $('#scc-main-settings-form').on('submit', function(e) {
            console.log('Main settings form submitted');
            
            // Check if any form fields are empty (just for debugging)
            var emptyFields = $(this).find('input[type="text"]').filter(function() {
                return $(this).val() === '';
            });
            
            if (emptyFields.length > 0) {
                console.warn('Warning: Form has empty fields:', emptyFields);
            }
            
            // Debug form data
            var formData = $(this).serialize();
            console.log('Form data:', formData);
            
            // Make sure the form actually submits (don't return false)
        });
        
        // Add cookie button handler - show the form
        $('.scc-category-section').each(function() {
            var categoryId = $(this).find('.scc-category-title-field').data('category-id');
            if (categoryId) {
                // Create a button to show the add cookie form
                var $addBtn = $('<button type="button" class="button">Add Cookie to this Category</button>');
                $(this).append($addBtn);
                
                // Show the add cookie form when button is clicked
                $addBtn.on('click', function(e) {
                    e.preventDefault();
                    $('#scc-add-cookie-form-' + categoryId).toggle();
                });
            }
        });
        
        // Highlight changed fields
        $('#scc-main-settings-form input, #scc-main-settings-form textarea, #scc-main-settings-form select').on('change', function() {
            $(this).css('background-color', '#ffffdd');
            console.log('Field changed:', $(this).attr('name'), 'New value:', $(this).val());
        });
    });
    </script>
    <?php
}

// Ensure proper defaults when getting options
function scc_get_merged_options() {
    $options = get_option('scc_options', array());
    $default_options = scc_get_default_options();
    
    // Merge with defaults to ensure all keys exist
    return wp_parse_args($options, $default_options);
}

// Enqueue scripts and styles
function scc_enqueue_scripts() {
    // Generate a version string based on the last time options were updated
    $version = get_option('scc_options_last_updated', '1.0.0');
    
    // Enqueue the bundled JavaScript file
    wp_enqueue_script('scc-cookieconsent', plugin_dir_url(__FILE__) . 'dist/cookieconsent.bundle.js', array(), $version, true);
    
    // Get options and ensure all keys exist
    $options = scc_get_merged_options();
    
    // Localize script with settings
    wp_localize_script('scc-cookieconsent', 'sccSettings', array(
        'settings' => $options,
        'version' => $version
    ));
    
    // Debug comment
    echo "<!-- Simple Cookie Consent plugin loaded (v{$version}) -->\n";
}

// Add a hook to update the timestamp when options are saved
add_action('update_option_scc_options', 'scc_update_options_timestamp', 10, 2);
function scc_update_options_timestamp($old_value, $new_value) {
    update_option('scc_options_last_updated', time());
}

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

// Update activation hook to properly set default options
register_activation_hook(__FILE__, 'scc_plugin_activate');
function scc_plugin_activate() {
    $options = get_option('scc_options', array());
    $default_options = scc_get_default_options();
    
    // Only add missing keys from defaults
    $merged_options = wp_parse_args($options, $default_options);
    
    // Update options with defaults for any missing values
    update_option('scc_options', $merged_options);
}

// Make sure cookie categories titles use specific CSS class for JavaScript targeting
function scc_render_category_title_field($category_id, $title) {
    ?>
    <input type="text" 
           name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][title]" 
           value="<?php echo esc_attr($title); ?>" 
           class="regular-text scc-category-title-field" 
           data-category-id="<?php echo esc_attr($category_id); ?>" />
    <p class="description">The name displayed to users in the consent preferences panel.</p>
    <?php
}