describe('Debug Connection', () => {
  it('Successfully loads the homepage', () => {
    // Use explicit IP to bypass localhost resolution issues
    cy.visit('http://127.0.0.1:8000');
    cy.title().should('include', 'Mossé Luxe');
    cy.log('Page loaded successfully');
  });
});
