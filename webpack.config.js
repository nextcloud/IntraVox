const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const NextcloudEntrypointsPlugin = require('./scripts/webpack-entrypoints');

const isDev = process.env.NODE_ENV === 'development';
const isAnalyze = process.env.ANALYZE === 'true';

// Conditionally load bundle analyzer
const plugins = [
    new VueLoaderPlugin(),
    new MiniCssExtractPlugin({
        filename: '../css/intravox-[name].css',
        chunkFilename: '../css/intravox-[name].[contenthash:8].css',
    }),
    new NextcloudEntrypointsPlugin(),
    new webpack.DefinePlugin({
        __VUE_OPTIONS_API__: JSON.stringify(true),
        __VUE_PROD_DEVTOOLS__: JSON.stringify(isDev),
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(isDev),
        appName: JSON.stringify('intravox')
    })
];

if (isAnalyze) {
    const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');
    plugins.push(new BundleAnalyzerPlugin({
        analyzerMode: 'server',
        openAnalyzer: true
    }));
}

module.exports = {
    entry: {
        main: './src/main.js',
        admin: './scripts/admin-entry.js'
    },
    output: {
        filename: 'intravox-[name].js',
        chunkFilename: 'intravox-[name].[contenthash:8].js',
        path: path.resolve(__dirname, 'js'),
        clean: true
    },
    optimization: {
        splitChunks: {
            chunks: 'all',
            maxSize: 190000,
            cacheGroups: {
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    idHint: 'vendors',
                    priority: -10,
                    reuseExistingChunk: true,
                },
                shared: {
                    idHint: 'shared',
                    minChunks: 2,
                    priority: -20,
                    reuseExistingChunk: true,
                },
            },
        },
    },
    module: {
        rules: [
            {
                test: /[\\/]@nextcloud[\\/]dialogs[\\/]dist[\\/]chunks[\\/]index-[\w-]+\.mjs$/,
                loader: path.resolve(__dirname, 'scripts/dialog-locale-chunks-loader.cjs'),
            },
            {
                test: /\.(vue|js)$/,
                include: path.resolve(__dirname, 'src'),
                enforce: 'pre',
                loader: path.resolve(__dirname, 'scripts/nextcloud-component-imports-loader.cjs'),
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    // Template comments are for developers and translators (the
                    // TRANSLATORS hints the Transifex extractor reads from source).
                    // Kept, Vue renders each one as a comment node: it ships in the
                    // bundle and a component that takes its first slot node as
                    // its label would get the comment instead.
                    compilerOptions: { comments: false },
                },
            },
            {
                test: /\.css$/,
                // Keep dependency component CSS with its owner. Extracting the
                // whole Nextcloud library creates contradictory entry ordering.
                use: ['style-loader', 'css-loader']
            },
            {
                test: /\.s[ac]ss$/i,
                use: [
                    isDev ? 'style-loader' : MiniCssExtractPlugin.loader,
                    'css-loader',
                    { loader: 'sass-loader', options: { api: 'modern' } },
                ],
            },
            {
                test: /\.js$/,
                loader: 'string-replace-loader',
                include: path.resolve(__dirname, 'node_modules/@nextcloud/vue'),
                options: {
                    search: '"appName"',
                    replace: '"intravox"',
                    flags: 'g'
                }
            }
        ]
    },
    plugins,
    resolve: {
        extensions: ['.js', '.vue'],
        alias: {
            '@': path.resolve(__dirname, 'src'),
            'vue': 'vue/dist/vue.runtime.esm-bundler.js'
        },
        fallback: {
            'path': false,
            'fs': false,
            'crypto': false,
            'stream': false,
            'string_decoder': false,
            'buffer': false,
            'os': false,
            'http': false,
            'https': false,
            'zlib': false
        }
    },
    mode: isDev ? 'development' : 'production',
    devtool: isDev ? 'source-map' : false
};
