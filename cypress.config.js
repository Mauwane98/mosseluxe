const { defineConfig } = require('cypress');

module.exports = defineConfig({
  projectId: 'av2voq',
  e2e: {
    pageLoadTimeout: 120000,
    baseUrl: 'http://localhost:8001',
    specPattern: 'cypress/e2e/comprehensive.cy.js',
    supportFile: 'cypress/support/e2e.js',
  },
  env: {
    percyOptions: {
      // Percy configuration
    }
  },
  retries: {
    runMode: 2,
    openMode: 0,
  },
  video: true,
  screenshotOnRunFailure: true,
  viewportWidth: 1280,
  viewportHeight: 720,
});
