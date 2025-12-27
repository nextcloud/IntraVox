const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const webpack = require('webpack');

const isDev = process.env.NODE_ENV === 'development';
const isAnalyze = process.env.ANALYZE === 'true';

// Conditionally load bundle analyzer
const plugins = [
    new VueLoaderPlugin(),
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
        admin: './src/admin.js'
    },
    output: {
        filename: 'intravox-[name].js',
        chunkFilename: 'intravox-[name].js',
        path: path.resolve(__dirname, 'js'),
        clean: true
    },
    optimization: {
        splitChunks: false
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader']
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
            'os': false,
            'http': false,
            'https': false,
            'zlib': false
        }
    },
    mode: isDev ? 'development' : 'production',
    devtool: isDev ? 'source-map' : false
};
