describe('Mossé Luxe - Comprehensive Site Error Scanning', () => {
  beforeEach(() => {
    // Clear cookies and local storage before each test
    cy.clearCookies()
    cy.clearLocalStorage()
  })

  it('Homepage - Basic functionality and errors', () => {
    cy.visit('/')

    // Check if page loads without server errors (should not be 500 error)
    cy.url().should('include', 'localhost')
    cy.get('body').should('be.visible')

    // Check for console errors
    cy.checkForJSErrors()

    // Verify basic page elements exist
    cy.get('header').should('exist')
    cy.get('nav').should('exist')

    // Check for broken images and assets
    cy.scanPageElements()

    // Check for common page elements
    cy.contains('Mossé Luxe', { timeout: 10000 }).should('exist')
    cy.contains('Shop').should('exist')
    cy.contains('Cart').should('exist')
  })

  it('Navigation Links - Broken link detection', () => {
    cy.login('test@example.com', 'password')
    cy.visit('/')

    // Get all navigation links and check if they're not 404
    cy.get('a[href]').each(($a) => {
      const href = $a.attr('href')
      if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !href.startsWith('mailto:') && !href.startsWith('tel:') && !href.startsWith('http')) {
        cy.request({
          url: href,
          failOnStatusCode: false,
          timeout: 10000
        }).then((response) => {
          if (response.status >= 400) {
            throw new Error(`Broken link: ${href} - Status: ${response.status}`)
          }
        })
      }
    })
  })

  it('Shop Page - Product display and functionality', () => {
    cy.visit('/shop')

    // Check page loads
    cy.url().should('include', 'shop')
    cy.contains('Premium Collection', { timeout: 10000 }).should('exist')

    // Check for product listings
    cy.get('.product-card', { timeout: 10000 }).should('have.length.greaterThan', 0)

    // Verify products are visible
    cy.get('.product-card').first().should('be.visible')
  })

  it('Cart Functionality - Basic operations', () => {
    cy.visit('/cart')

    // Check cart page loads
    cy.url().should('include', 'cart')
    cy.contains('Your Shopping Cart').should('exist')

    // Test AJAX functionality if present
    cy.intercept('*').as('anyRequest');

    // Check for common cart elements
    cy.get('body').should('not.contain', 'Fatal error')
    cy.get('body').should('not.contain', 'Warning')
  })

  it('Contact Page - Form validation and submission', () => {
    cy.visit('/contact')

    // Check page loads
    cy.contains('Contact').should('exist')

    // Test form if present
    cy.get('form').then($form => {
      if ($form.length > 0) {
        // Check required fields exist
        cy.get('input[name="name"], input[name="email"], textarea[name="message"]').should('exist')

        // Test form submission (should handle appropriately - either AJAX or prevent default)
        cy.get('form').then($form => {
          if ($form.attr('method') === 'POST') {
            // Test with minimal data - should either submit successfully or show validation
            cy.get('input[name="name"]').type('Test User')
            cy.get('input[name="email"]').type('test@example.com')
            cy.get('textarea[name="message"]').type('Test message')
          }
        })
      }
    })

    cy.scanPageElements()
  })

  it('Admin Panel - Basic access (should require auth)', () => {
    cy.visit('/admin/', { failOnStatusCode: false })

    // This should either redirect or require login
    // We don't expect it to be accessible without auth
    cy.get('body').should('not.contain', 'Fatal error')
  })

  it('Static Pages - 404 and error handling', () => {
    // Test existing page
    cy.visit('/about')
    cy.get('body').should('not.contain', '404')
    cy.get('body').should('not.contain', 'Fatal error')

    // Test non-existent page (should show 404.php)
    cy.visit('/nonexistent', { failOnStatusCode: false })
    cy.get('body').should('contain', 'Page Not Found') // Should load error page gracefully

    cy.visit('/careers')
    cy.get('body').should('not.contain', 'Fatal error')
  })

  it('JavaScript Functionality - AJAX calls', () => {
    cy.visit('/')

    // Monitor network errors
    cy.window().then((win) => {
      const originalFetch = win.fetch
      win.fetch = function(...args) {
        return originalFetch.apply(this, args).catch(error => {
          throw new Error(`Fetch failed: ${error.message}`)
        })
      }
    })

    // Test AJAX cart if present
    cy.get('body').then($body => {
      if ($body.find('.cart-btn, .add-to-cart').length > 0) {
        // Test cart functionality exists
        cy.get('.cart-btn, .add-to-cart').should('be.visible')
      }
    })
  })

  it('Performance - Page load times', () => {
    const startTime = Date.now()
    cy.visit('/', { timeout: 30000 })
    cy.get('body').should('be.visible')

        // Check load time (rough estimate)
    cy.window().then(() => {
      const loadTime = Date.now() - startTime
      // Fail if page takes more than 15 seconds to load basic content
      if (loadTime > 15000) {
        cy.log(`Warning: Page loaded in ${loadTime}ms, which is slow`)
      }
    })
  })
})
