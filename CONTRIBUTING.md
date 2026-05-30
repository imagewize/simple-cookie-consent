# Contributing to Warder Cookie Consent

Thanks for your interest in improving Warder Cookie Consent! This guide covers how to
build the plugin from source, set up a development environment, and submit changes.

For a deeper technical reference on how the plugin loads settings, blocks scripts, and
manages cookies, see [`docs/dev.md`](docs/dev.md).

## Build from Source

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

## Development

PHP 8.0+ is required. To set up the development environment:

```bash
# Install dependencies
npm install

# Build for production
npx webpack

# Watch for changes
npx webpack --watch
```

The frontend bundle is built from `src/index.js` into `dist/cookieconsent.bundle.js`.
There are no automated tests.

## Dependencies

- [CookieConsent v3](https://github.com/orestbida/cookieconsent) - Core cookie consent functionality
- [webpack](https://webpack.js.org/) - For bundling assets
- [style-loader](https://webpack.js.org/loaders/style-loader/) - For loading CSS
- [css-loader](https://webpack.js.org/loaders/css-loader/) - For processing CSS

## Submitting Changes

1. Fork the repository and create a feature branch off `main`.
2. Make your changes and rebuild the bundle (`npx webpack`) if you touched anything under `src/`.
3. Open a pull request against `main` with a clear description of what changed and why.
