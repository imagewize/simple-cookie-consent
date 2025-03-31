// Enqueue the cookieconsent scripts and styles
function enqueue_cookieconsent_scripts() {
    // Get the theme directory URI
    $theme_directory = get_template_directory_uri();

    // Enqueue the vanilla-cookieconsent JavaScript and CSS files
    wp_enqueue_script('cookieconsent-js', $theme_directory . '/node_modules/vanilla-cookieconsent/dist/cookieconsent.js', array(), '3.1.0', true);
    wp_enqueue_style('cookieconsent-css', $theme_directory . '/node_modules/vanilla-cookieconsent/dist/cookieconsent.css', array(), '3.1.0');
}
add_action('wp_enqueue_scripts', 'enqueue_cookieconsent_scripts');