// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

Cypress.Commands.add('checkForJSErrors', () => {
  cy.window().then((win) => {
    cy.stub(win.console, 'error').as('consoleError')
    cy.stub(win.console, 'warn').as('consoleWarn')
  })
})

Cypress.Commands.add('verifyResponseCode', (url, expectedCode = 200) => {
  cy.request(url).then((response) => {
    expect(response.status).to.eq(expectedCode)
  })
})

Cypress.Commands.add('scanPageElements', () => {
  // Check for common broken elements
  cy.get('body').then(() => {
    // Check for missing images
    cy.get('img').each(($img) => {
      // Skip data: URLs and certain placeholders
      if (!$img.attr('src')?.startsWith('data:') && !$img.attr('src')?.includes('placeholder')) {
        cy.request($img.attr('src')).then((response) => {
          if (response.status !== 200) {
            throw new Error(`Broken image: ${$img.attr('src')} - Status: ${response.status}`)
          }
        })
      }
    })

    // Check for missing CSS/JS files
    cy.get('link').each(($link) => {
      if ($link.attr('rel') === 'stylesheet') {
        cy.request($link.attr('href')).then((response) => {
          if (response.status !== 200) {
            throw new Error(`Missing CSS file: ${$link.attr('href')} - Status: ${response.status}`)
          }
        })
      }
    })

    cy.get('script').each(($script) => {
      if ($script.attr('src')) {
        cy.request($script.attr('src')).then((response) => {
          if (response.status !== 200) {
            throw new Error(`Missing JS file: ${$script.attr('src')} - Status: ${response.status}`)
          }
        })
      }
    })
  })
})

Cypress.Commands.add('login', (email, password) => {
  // Visit login page to get CSRF token and cookie
  cy.visit('/login');
  
  cy.get('input[name="csrf_token"]').invoke('val').then((token) => {
    cy.request({
      method: 'POST',
      url: '/login',
      form: true,
      body: {
        email: email,
        password: password,
        csrf_token: token
      }
    });
  });
});
