module.exports = {
  browserSync: {
    hostname: "aucor.allie",
    port: 3002,
    openAutomatically: false,
    reloadDelay: 50,
    injectChanges: true,
  },

  drush: {
    enabled: true,
    alias: {
      css_js: 'drush @aucor.local cc css-js',
      cr: 'drush @aucor.local cc all'
    }
  },

  tpl: {
    rebuildOnChange: true
  }
};