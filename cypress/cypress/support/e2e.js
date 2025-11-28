// Import Percy for visual testing
import '@percy/cypress';

Cypress.on('uncaught:exception', (err, runnable) => {
  // returning false here prevents Cypress from
  // failing the test on unhandled exceptions
  return false
})

// Cypress test configuration
beforeEach(() => {
  // Clear all local storage before each test
  cy.clearLocalStorage()
  cy.clearCookies()

  // Set up test data if needed
})

// Global commands
Cypress.Commands.add('visitSite', (path = '') => {
  cy.visit(`/${path}`)
})

Cypress.Commands.add('login', (email, password) => {
  // Implement login command if authentication is added
})

export const config = {
  baseUrl: 'http://localhost',
  basePath: '/mosseluxe', // When running in subdirectory
  timeout: 10000
}
