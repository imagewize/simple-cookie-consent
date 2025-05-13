# Simple Cookie Consent

A lightweight plugin that implements GDPR-compliant cookie consent functionality for WordPress websites using the [vanilla-cookieconsent](https://github.com/orestbida/cookieconsent) library.

## Features

- 🚀 Lightweight and fast
- 🌐 Multi-language support
- 🎨 Customizable appearance
- 🔒 GDPR-compliant cookie management
- 📱 Fully responsive design
- 🧩 Easy integration with WordPress

## Installation

### Method 1: Using WordPress Admin

1. Download the plugin ZIP file from the releases page
2. Go to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. Activate the plugin

### Method 2: Manual Installation

1. Download the plugin ZIP file
2. Extract the ZIP file
3. Upload the extracted folder to your `/wp-content/plugins/` directory
4. Activate the plugin through the WordPress admin panel

### Method 3: Build from Source

1. Clone this repository
2. Install dependencies:
   ```bash
   npm install
   ```
3. Build the plugin:
   ```bash
   npx webpack
   ```
4. Upload the plugin folder to your WordPress plugins directory

### Method 4: Using Composer

You can also install the plugin using Composer:
```bash
composer require imagewize/simple-cookie-consent
```

## Usage

After activation, the cookie consent banner will automatically appear on your website. Configure the settings from the WordPress admin panel at Settings > Cookie Consent.

## Configuration

You can customize the plugin through the admin interface:

1. Navigate to Settings > Cookie Consent in your WordPress dashboard
2. Configure the following settings:
   - Language and general behavior
   - Banner title and description
   - Button text and actions
   - Privacy policy link
   - **Cookie categories and patterns** (NEW!)

### NEW: Cookie Categories Management

The plugin now allows you to manage cookie categories and patterns directly through the admin interface:

- **Add new cookie categories** like Marketing or Preferences
- **Define specific cookies** to block within each category
- **Use regular expressions** to match multiple cookies with similar names
- **Automatically clear cookies** when consent is withdrawn

This means you can easily configure which cookies to block and when to allow them, all without editing any code.

### Cookie Categories

The default configuration includes:
- **Necessary cookies**: Always enabled, required for basic website functionality
- **Analytics cookies**: Optional tracking and analytics cookies

## Usage Examples

### Blocking Google Analytics Until Consent

Add your Google Analytics script with the `data-category` attribute:

```html
<script type="text/plain" data-category="analytics">
  // Your Google Analytics code here
</script>
```

The script won't execute until the visitor accepts analytics cookies.

### Managing Third-party Cookies

The plugin automatically handles third-party cookies by:
1. Preventing scripts from loading until consent is given
2. Clearing cookies if consent is withdrawn

For detailed implementation guides, see the `dev.md` documentation file.

## Dependencies

- [CookieConsent v3](https://github.com/orestbida/cookieconsent) - Core cookie consent functionality
- [webpack](https://webpack.js.org/) - For bundling assets
- [style-loader](https://webpack.js.org/loaders/style-loader/) - For loading CSS
- [css-loader](https://webpack.js.org/loaders/css-loader/) - For processing CSS

## Development

To set up the development environment:

```bash
# Install dependencies
npm install

# Build for production
npx webpack

# Watch for changes
npx webpack --watch
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.