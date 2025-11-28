import { test, expect } from "@playwright/test";
import AxeBuilder from "@axe-core/playwright";
import { runLighthouse } from "./lighthouse-runner.js";

const SITE = "http://localhost/mosseluxe";

test.describe("🔥 FULL PRODUCTION TEST SUITE", () => {

  test("1. Homepage loads with no JS errors", async ({ page }) => {
    const errors = [];

    page.on("console", (msg) => {
      if (msg.type() === "error") errors.push(msg.text());
    });

    await page.goto(SITE, { waitUntil: "networkidle" });

    expect(await page.title()).not.toBeNull();
    expect(errors).toHaveLength(0);
  });

  test("2. Product listing & details", async ({ page }) => {
    await page.goto(`${SITE}/shop.php`);

    const product = page.locator('.grid.grid-cols-2 a[href*="product/"]').first();
    await expect(product).toBeVisible();

    await product.click();
    await expect(page.locator('h1')).toBeVisible();

    const addToCartBtn = page.locator('button:has-text("Add to Cart")');
    await expect(addToCartBtn).toBeVisible();
  });

  test("3. Add to cart & remove", async ({ page }) => {
    await page.goto(`${SITE}/shop.php`);
    await page.locator('.grid.grid-cols-2 a[href*="product/"]').first().click();
    await page.locator('button:has-text("Add to Cart")').click();

    const count = page.locator("#cart-count");
    await expect(count).not.toHaveText("0");

    await page.goto(`${SITE}/cart.php`);
    await expect(page.locator('h1').filter({ hasText: 'Your Shopping Cart' })).toBeVisible();

    await page.locator('.remove-from-cart-btn').first().click();
    await page.waitForTimeout(1000);
  });

  test("4. API health check", async ({ request }) => {
    const response = await request.get(`${SITE}/api/products`);
    expect(response.ok()).toBeTruthy();
  });

  test("5. Mobile responsiveness", async ({ browser }) => {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
    await page.goto(SITE);
    const mobileMenuBtn = page.locator('#open-menu-btn');
    await expect(mobileMenuBtn).toBeVisible();
  });

  test("6. Accessibility test (Axe)", async ({ page }) => {
    await page.goto(SITE);

    const results = await new AxeBuilder({ page }).analyze();
    expect(results.violations, "Accessibility violations found").toEqual([]);
  });

  test("7. Lighthouse audit", async () => {
    const results = await runLighthouse(SITE);

    expect(results.performance).toBeGreaterThan(70);
    expect(results.accessibility).toBeGreaterThan(70);
    expect(results.seo).toBeGreaterThan(70);
    expect(results.bestPractices).toBeGreaterThan(70);
  });

  test("8. Screenshot visual regression", async ({ page }) => {
    await page.goto(SITE);
    expect(await page.screenshot()).toMatchSnapshot("homepage.png");
  });

});
