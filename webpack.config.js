const path = require('path');
const webpack = require('webpack');

module.exports = {
    entry: './src/index.js',
    output: {
        filename: 'cookieconsent.bundle.js',
        path: path.resolve(__dirname, 'dist'),
    },
    mode: 'production',
    module: {
        rules: [
            {
                test: /\.css$/i,
                use: ['style-loader', 'css-loader'],
            },
        ],
    },
    plugins: [
        // Provide global access to the initCookieConsent function
        new webpack.ProvidePlugin({
            initCookieConsent: ['vanilla-cookieconsent', 'initCookieConsent']
        })
    ]
};