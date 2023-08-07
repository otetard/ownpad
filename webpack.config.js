const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

webpackConfig.entry.settings = path.resolve(path.join('src', 'settings.js'))

module.exports = webpackConfig
