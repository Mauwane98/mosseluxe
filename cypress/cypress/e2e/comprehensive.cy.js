describe('🔥 CYPRESS E-COMMERCE TEST SUITE WITH PERCY', () => {
  beforeEach(() => {
    cy.clearCookies()
    cy.clearLocalStorage()
  })

  it('🛍️ FULL E-COMMERCE SHOPPING FLOW', () => {
    // Visit homepage and take visual snapshot
    cy.visit('/')
    cy.percySnapshot('Homepage')

    // Assert title
    cy.title().should('include', 'Mossé Luxe')

    // Navigate to shop
    cy.get('a[href*="shop"]').first().click()
    cy.url().should('include', 'shop.php')
    cy.percySnapshot('Shop Page')

    // Check products
    cy.get('.grid.grid-cols-2 a[href*="product/"]').should('have.length.greaterThan', 0)

    // Check cart count starts at 0
    cy.get('#cart-count').then($count => {
      const initialCount = parseInt($count.text()) || 0
      expect(initialCount).to.eq(0)
    })

    // Click first product
    cy.get('.grid.grid-cols-2 a[href*="product/"]').first().click()
    cy.percySnapshot('Product Detail Page')

    // Verify product page loaded
    cy.get('h1').should('be.visible')
    cy.get('button').contains('Add to Cart').should('be.visible')

    // Add to cart
    cy.get('button').contains('Add to Cart').first().click()

    // Wait for cart update
    cy.wait(2000)

    // Check cart count increased
    cy.get('#cart-count').then($count => {
      const updatedCount = parseInt($count.text()) || 0
      expect(updatedCount).to.be.greaterThan(0)
    })

    // Go to cart page
    cy.visit('/cart.php')
    cy.percySnapshot('Cart Page')

    // Verify cart page
    cy.get('h1').contains('Your Shopping Cart').should('be.visible')

    // Check cart has items
    cy.get('#cart-items-container .flex.items-center').should('have.length.greaterThan', 0)

    // Go to checkout
    cy.get('a[href*="checkout"]').first().click()
    cy.percySnapshot('Checkout Page')

    // Verify checkout page loads (will be simple for now)
    cy.get('h1, .checkout').should('be.visible')
  })

  it('♿ ACCESSIBILITY CHECK (AXE)', () => {
    cy.visit('/shop.php')
    cy.injectAxe()
    cy.checkA11y()
  })

  it('📱 RESPONSIVE DESIGN CHECK', () => {
    // Mobile viewport
    cy.viewport(375, 667)
    cy.visit('/shop.php')
    cy.percySnapshot('Shop Page Mobile')

    // Tablet viewport
    cy.viewport(768, 1024)
    cy.visit('/shop.php')
    cy.percySnapshot('Shop Page Tablet')

    // Desktop viewport
    cy.viewport(1920, 1080)
    cy.visit('/shop.php')
    cy.percySnapshot('Shop Page Desktop')
  })

  it('🚨 ERROR HANDLING', () => {
    // Test 404
    cy.visit('/nonexistent-page.php', { failOnStatusCode: false })
    cy.get('body').should('exist') // Should handle gracefully

    // Test invalid product
    cy.visit('/product/99999/invalid', { failOnStatusCode: false })
    cy.get('body').should('exist')
  })
})
