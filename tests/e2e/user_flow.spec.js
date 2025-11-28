const { test, expect } = require('@playwright/test');

test.describe('👤 USER EXPERIENCE FLOWS', () => {
  test.use({
    baseURL: process.env.BASE_URL || 'http://localhost/mosseluxe',
    viewport: { width: 1280, height: 720 },
  });

  test('User registration flow', async ({ page }) => {
    await page.goto('/register.php');
    await expect(page.locator('h1').filter({ hasText: 'Register' })).toBeVisible();

    // Check registration form
    const registerForm = page.locator('form[action*="register"], form#register-form');
    await expect(registerForm).toBeVisible();

    // Fields presence
    await expect(page.locator('input[name="username"], input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('Login functionality', async ({ page }) => {
    await page.goto('/login.php');
    await expect(page.locator('h1').filter({ hasText: 'Login' })).toBeVisible();

    // Login form
    const loginForm = page.locator('form[action*="login"], form#login-form');
    await expect(loginForm).toBeVisible();

    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('Search functionality', async ({ page }) => {
    await page.goto('/');

    // Find search input
    const searchInput = page.locator('input[name="search"], input[name="query"], input[type="search"]');
    if (await searchInput.isVisible()) {
      await expect(searchInput).toBeVisible();

      // Search button or form
      const searchBtn = page.locator('button[type="submit"], button:has-text("Search")');
      await expect(searchBtn).toBeVisible();

      // Perform simple search
      await searchInput.fill('hoodie');
      await searchBtn.click();

      // Check results page
      await page.waitForURL('**/search.php**');
      await expect(page.locator('h1').filter({ hasText: 'Search' })).toBeVisible();
    }
  });

  test('Wishlist functionality', async ({ page }) => {
    // Navigate to product details
    await page.goto('/product.php?id=5');

    // Add to wishlist (if user logged in or guest wishlist supported)
    const wishlistBtn = page.locator('#wishlist-toggle-btn');
    if (await wishlistBtn.isVisible()) {
      await wishlistBtn.click();
      // Check for success feedback
    }

    // Navigate to wishlist page
    await page.goto('/wishlist.php');
    await expect(page.locator('h1').filter({ hasText: 'Wishlist' })).toBeVisible();
  });

  test('Checkout process completes form', async ({ page }) => {
    // Add product to cart first
    await page.goto('/product.php?id=5');

    const addToCartBtn = page.locator('button:has-text("Add to Cart")');
    if (await addToCartBtn.isVisible()) {
      await addToCartBtn.click();
      await page.waitForTimeout(1000); // Wait for AJAX
    }

    // Go to checkout
    await page.goto('/checkout.php');
    await expect(page.locator('h1').filter({ hasText: 'Checkout' })).toBeVisible();

    // Check form sections
    await expect(page.locator('input[name*="name" i], input[name*="first_name"]')).toBeVisible();
    await expect(page.locator('input[name*="email"]')).toBeVisible();
    await expect(page.locator('input[name*="phone"], input[name*="telephone"]')).toBeVisible();

    // Address fields
    await expect(page.locator('input[name*="address"], textarea[name*="address"]')).toBeVisible();

    // Payment section
    const paymentSection = page.locator('#payment-section, .payment-method');
    if (await paymentSection.exists()) {
      await expect(paymentSection).toBeVisible();
    }
  });
});
