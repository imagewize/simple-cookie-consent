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
            
            $valid['cookie_categories'][$sanitized_id] = array(
                'title' => sanitize_text_field($category['title']),
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
    
    // Get options and ensure they include cookie_categories
    $options = get_option('scc_options', array());
    $default_options = scc_get_default_options();
    
    // Merge with defaults to ensure all keys exist
    $options = wp_parse_args($options, $default_options);
    
    // Ensure cookie_categories exists and is an array
    if (!isset($options['cookie_categories']) || !is_array($options['cookie_categories'])) {
        $options['cookie_categories'] = $default_options['cookie_categories'];
    }
    
    // Handle adding a new category
    if (isset($_POST['scc_add_category']) && isset($_POST['new_category_id']) && !empty($_POST['new_category_id'])) {
        check_admin_referer('scc_add_category', 'scc_category_nonce');
        
        $new_id = sanitize_key($_POST['new_category_id']);
        
        if (!isset($options['cookie_categories'][$new_id])) {
            $options['cookie_categories'][$new_id] = array(
                'title' => ucfirst($new_id),
                'description' => '',
                'enabled' => false,
                'readonly' => false,
                'cookies' => array()
            );
            
            update_option('scc_options', $options);
        }
    }
    
    // Handle adding a new cookie to a category
    if (isset($_POST['scc_add_cookie']) && isset($_POST['category_id']) && isset($_POST['cookie_name'])) {
        check_admin_referer('scc_add_cookie', 'scc_cookie_nonce');
        
        $category_id = sanitize_key($_POST['category_id']);
        $cookie_name = sanitize_text_field($_POST['cookie_name']);
        $is_regex = isset($_POST['is_regex']) ? true : false;
        
        if (isset($options['cookie_categories'][$category_id]) && !empty($cookie_name)) {
            $options['cookie_categories'][$category_id]['cookies'][] = array(
                'name' => $cookie_name,
                'is_regex' => $is_regex
            );
            
            update_option('scc_options', $options);
        }
    }
    
    // Handle removing a cookie category
    if (isset($_GET['action']) && $_GET['action'] === 'delete_category' && isset($_GET['category']) && isset($_GET['_wpnonce'])) {
        $category_id = sanitize_key($_GET['category']);
        
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_category_' . $category_id) && isset($options['cookie_categories'][$category_id])) {
            // Don't allow deleting the necessary category
            if ($category_id !== 'necessary') {
                unset($options['cookie_categories'][$category_id]);
                update_option('scc_options', $options);
            }
        }
    }
    
    // Handle removing a cookie
    if (isset($_GET['action']) && $_GET['action'] === 'delete_cookie' && isset($_GET['category']) && isset($_GET['cookie_index']) && isset($_GET['_wpnonce'])) {
        $category_id = sanitize_key($_GET['category']);
        $cookie_index = intval($_GET['cookie_index']);
        
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_cookie_' . $category_id . '_' . $cookie_index) && 
            isset($options['cookie_categories'][$category_id]['cookies'][$cookie_index])) {
            
            array_splice($options['cookie_categories'][$category_id]['cookies'], $cookie_index, 1);
            update_option('scc_options', $options);
        }
    }
    
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
            
            <h2>Cookie Categories</h2>
            <p>Configure cookie categories and specific cookies to be blocked until consent is given.</p>
            
            <?php 
            // Ensure cookie_categories is an array before trying to iterate
            if (isset($options['cookie_categories']) && is_array($options['cookie_categories'])) {
                foreach ($options['cookie_categories'] as $category_id => $category) : 
            ?>
                <div class="scc-category-section" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                    <h3 style="margin-top: 0;"><?php echo esc_html($category['title']); ?> (<?php echo esc_html($category_id); ?>)</h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Title</th>
                            <td>
                                <input type="text" name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][title]" 
                                    value="<?php echo esc_attr($category['title']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Description</th>
                            <td>
                                <textarea name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][description]" 
                                    rows="2" class="large-text"><?php echo esc_textarea($category['description']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Settings</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][enabled]" 
                                        <?php checked($category['enabled'], true); ?> />
                                    Enabled by default
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][readonly]" 
                                        <?php checked($category['readonly'], true); ?> 
                                        <?php if ($category_id === 'necessary') echo 'disabled'; ?> />
                                    Read-only (user cannot change)
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <h4>Cookies in this category</h4>
                    
                    <?php if (!empty($category['cookies'])) : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Cookie Name / Pattern</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category['cookies'] as $index => $cookie) : ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" 
                                                name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][cookies][<?php echo $index; ?>][name]" 
                                                value="<?php echo esc_attr($cookie['name']); ?>" />
                                            <?php echo esc_html($cookie['name']); ?>
                                        </td>
                                        <td>
                                            <input type="hidden" 
                                                name="scc_options[cookie_categories][<?php echo esc_attr($category_id); ?>][cookies][<?php echo $index; ?>][is_regex]" 
                                                value="<?php echo $cookie['is_regex'] ? '1' : ''; ?>" 
                                                <?php checked($cookie['is_regex'], true); ?> />
                                            <?php echo $cookie['is_regex'] ? 'Regular Expression' : 'Exact Match'; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo wp_nonce_url(
                                                add_query_arg(
                                                    array(
                                                        'page' => 'simple-cookie-consent',
                                                        'action' => 'delete_cookie',
                                                        'category' => $category_id,
                                                        'cookie_index' => $index
                                                    ),
                                                    admin_url('options-general.php')
                                                ),
                                                'delete_cookie_' . $category_id . '_' . $index
                                            ); ?>" class="button button-small" onclick="return confirm('Are you sure you want to remove this cookie?');">
                                                Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p>No cookies defined for this category yet.</p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 10px;">
                        <form method="post" action="">
                            <?php wp_nonce_field('scc_add_cookie', 'scc_cookie_nonce'); ?>
                            <input type="hidden" name="category_id" value="<?php echo esc_attr($category_id); ?>" />
                            <input type="text" name="cookie_name" placeholder="Cookie name or pattern (e.g. _ga or /^_ga/)" class="regular-text" />
                            <label>
                                <input type="checkbox" name="is_regex" />
                                Regular Expression
                            </label>
                            <input type="submit" name="scc_add_cookie" value="Add Cookie" class="button button-secondary" />
                        </form>
                    </div>
                    
                    <?php if ($category_id !== 'necessary') : ?>
                        <div style="margin-top: 10px; text-align: right;">
                            <a href="<?php echo wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'page' => 'simple-cookie-consent',
                                        'action' => 'delete_category',
                                        'category' => $category_id
                                    ),
                                    admin_url('options-general.php')
                                ),
                                'delete_category_' . $category_id
                            ); ?>" class="button button-link-delete" onclick="return confirm('Are you sure you want to remove this category?');">
                                Delete Category
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php 
                endforeach;
            } else {
                echo '<p>No cookie categories found. Default categories will be created when you save settings.</p>';
            }
            ?>
            
            <div style="margin: 20px 0;">
                <form method="post" action="">
                    <?php wp_nonce_field('scc_add_category', 'scc_category_nonce'); ?>
                    <input type="text" name="new_category_id" placeholder="New category ID (e.g. marketing)" class="regular-text" />
                    <input type="submit" name="scc_add_category" value="Add New Category" class="button button-secondary" />
                </form>
            </div>
            
            <?php submit_button('Save All Settings'); ?>
        </form>
    </div>
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
    // Enqueue the bundled JavaScript file
    wp_enqueue_script('scc-cookieconsent', plugin_dir_url(__FILE__) . 'dist/cookieconsent.bundle.js', array(), '1.0.0', true);
    
    // Get options and ensure all keys exist
    $options = scc_get_merged_options();
    
    // Localize script with settings
    wp_localize_script('scc-cookieconsent', 'sccSettings', array(
        'settings' => $options,
        'version' => time() 
    ));
    
    // Debug comment
    echo "<!-- Simple Cookie Consent plugin loaded -->\n";
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