const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const webpack = require('webpack');

module.exports = {
    entry: {
        main: './src/main.js'
    },
    output: {
        filename: 'intravox-[name].js',
        path: path.resolve(__dirname, 'js'),
        clean: true
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
    plugins: [
        new VueLoaderPlugin(),
        new webpack.DefinePlugin({
            __VUE_OPTIONS_API__: JSON.stringify(true),
            __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
            appName: JSON.stringify('intravox')
        })
    ],
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
    mode: 'production'
};
