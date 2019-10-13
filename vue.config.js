const path = require('path')

module.exports = {
  productionSourceMap: false,
  configureWebpack: {
    resolve: {
      alias: {
        '@': path.join(__dirname, 'assets')
      }
    },
    entry: {
      app: path.join(__dirname, 'assets/js', 'main.js')
    }
  },
  chainWebpack: config => {
    config.plugin('copy').tap(([options]) => {
      options[0].from = 'assets/public'
    })

    config.plugins.delete('prefetch')
  },
  assetsDir: 'build',
  outputDir: 'public',
  indexPath: '../templates/index.html'
}
