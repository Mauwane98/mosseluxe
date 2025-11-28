// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Global error handling - log all console errors
Cypress.on('window:error', (error) => {
  // Log the console error to the Cypress command log
  console.error('Console error detected:', error.message)
  throw error // Re-throw to fail the test
})

// Catch unhandled promise rejections
Cypress.on('unhandled:exception', (err, runnable) => {
  // Return false to prevent Cypress from failing the test
  console.error('Unhandled exception:', err.message)
  return false
})

beforeEach(() => {
  // Disable service workers before each test
  if (window.navigator && navigator.serviceWorker) {
    navigator.serviceWorker.getRegistrations().then((registrations) => {
      for (let registration of registrations) {
        registration.unregister()
      }
    })
  }
})
