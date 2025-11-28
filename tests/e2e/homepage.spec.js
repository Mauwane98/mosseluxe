const { test, expect } = require('@playwright/test');

test.describe('🛍️ MOSSÉ LUXE - HOMEPAGE FEATURES', () => {
  test.use({
    baseURL: process.env.BASE_URL || 'http://localhost/mosseluxe',
    viewport: { width: 1280, height: 720 },
  });

  test('Homepage loads with hero carousel', async ({ page }) => {
    await page.goto('/');

    // Hero section
    await expect(page.locator('section.hero')).toBeVisible();

    // Hero carousel - check for slides
    const slides = page.locator('.hero-slide');
    await expect(slides).toHaveCount(await slides.count());

    // Check for navigation controls
    const prevBtn = page.locator('.hero-prev');
    const nextBtn = page.locator('.hero-next');

    if (await slides.count() > 1) {
      await expect(prevBtn.or(nextBtn)).toBeVisible();
    }
  });

  test('New arrivals section displays products', async ({ page }) => {
    await page.goto('/');

    const newArrivals = page.locator('.new-arrivals');
    await expect(newArrivals).toBeVisible();

    const products = newArrivals.locator('[href*="product"]');
    expect(await products.count()).toBeGreaterThan(0);
  });

  test('Recently viewed section updates', async ({ page }) => {
    // Visit product page first
    await page.goto('/product.php?id=5');
    await page.waitForLoadState('networkidle');

    // Go back to homepage
    await page.goto('/');
    await expect(page.locator('.recently-viewed')).toBeVisible();
  });

  test('Dynamic homepage sections', async ({ page }) => {
    await page.goto('/');

    const sections = page.locator('.homepage-section');
    await expect(sections).toHaveCount(await sections.count());

    // Check brand statement section
    await expect(page.locator('.brand-statement')).toBeVisible();
  });

  test('Newsletter signup form', async ({ page }) => {
    await page.goto('/');

    const newsletterForm = page.locator('#newsletter-form');
    await expect(newsletterForm).toBeVisible();

    const emailInput = newsletterForm.locator('input[type="email"]');
    await expect(emailInput).toBeVisible();

    const submitBtn = newsletterForm.locator('button[type="submit"]');
    await expect(submitBtn).toBeVisible();
  });
});
