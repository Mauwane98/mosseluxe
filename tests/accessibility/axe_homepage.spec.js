const { test, expect } = require('@playwright/test');
const { injectAxe, checkA11y } = require('axe-playwright');

test.describe('♿ HOMEPAGE ACCESSIBILITY TESTS', () => {
  test.use({
    baseURL: process.env.BASE_URL || 'http://localhost/mosseluxe',
    viewport: { width: 1280, height: 720 },
  });

  test('Homepage accessibility compliance', async ({ page }) => {
    await page.goto('/');

    // Inject axe into page
    await injectAxe(page);

    // Run accessibility check
    const results = await checkA11y(page, null, {
      detailedReport: true,
      detailedReportOptions: {
        html: true,
      },
    });

    const criticalViolations = results.violations.filter(v =>
      v.impact === 'critical' || v.impact === 'serious'
    );

    // Assert no critical violations for compliance
    expect(criticalViolations.length).toBe(0);
  });

  test('Homepage keyboard navigation', async ({ page }) => {
    await page.goto('/');

    // Test focusable elements
    await page.keyboard.press('Tab');

    // Check if something receives focus
    const focusedElement = await page.$(':focus');
    expect(focusedElement).toBeTruthy();
  });
});
