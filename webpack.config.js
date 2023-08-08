const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

webpackConfig.entry.viewer = path.resolve(path.join('src', 'viewer.js'))
webpackConfig.entry.public = path.resolve(path.join('src', 'public.js'))
webpackConfig.entry.settings = path.resolve(path.join('src', 'settings.js'))

module.exports = webpackConfig
