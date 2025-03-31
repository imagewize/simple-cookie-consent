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

For advanced customization, you can modify the configuration in `src/index.js` and rebuild the plugin.

### Cookie Categories

The default configuration includes:
- **Necessary cookies**: Always enabled, required for basic website functionality
- **Analytics cookies**: Optional tracking and analytics cookies

## Dependencies

- [vanilla-cookieconsent](https://github.com/orestbida/cookieconsent) - Core cookie consent functionality
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