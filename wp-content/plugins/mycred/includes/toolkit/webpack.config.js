const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaults,
  externals: {
    react: 'React',            // Use WordPress' React
    'react-dom': 'ReactDOM',   // Use WordPress' ReactDOM
  },
  entry: {
    admin: './src/admin/index.js', // Your entry point
  },
  output: {
    path: path.resolve(__dirname, 'build'),
    filename: '[name].bundle.js', // Output as admin.bundle.js
  },
  module: {
    rules: [
      ...defaults.module.rules,
      {
        test: /\.svg$/,          // Add support for SVG files
        use: ['@svgr/webpack', 'url-loader'],
      },
    ],
  },
};
