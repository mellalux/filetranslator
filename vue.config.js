const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,

  //publicPath: process.env.NODE_ENV === 'production' ? '/synth/' : '/',
  chainWebpack: config => {
    config.plugin('html').tap(args => {
      args[0].title = 'Translate the files';
      return args;
    });
  },

  publicPath: '/filetrans',
  runtimeCompiler: true
})
