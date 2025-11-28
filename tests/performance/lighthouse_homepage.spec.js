const { test, expect } = require('@playwright/test');
const lighthouse = require('lighthouse');

test.describe('⚡ LIGHTHOUSE PERFORMANCE TESTS', () => {
  test.use({
    baseURL: process.env.BASE_URL || 'http://localhost/mosseluxe',
    viewport: { width: 1280, height: 720 },
  });

  test('Homepage Lighthouse audit', async ({ browser }) => {
    const page = await browser.newPage();
    await page.goto('/');

    // Run Lighthouse audit
    const options = {
      logLevel: 'info',
      disableDeviceEmulation: true,
      disableCpuThrottling: false,
      disableNetworkThrottling: false,
      throttling: {
        rttMs: 150,
        throughputKbps: 1500,
        cpuSlowdownMultiplier: 4,
      },
    };

    try {
      const runnerResult = await lighthouse(await page.url(), options);

      const scores = runnerResult.lhr.categories;

      // Store detailed metrics
      const metrics = {
        performance: scores.performance.score,
        accessibility: scores.accessibility.score,
        seo: scores.seo.score,
        bestPractices: scores['best-practices'].score,
        timestamp: new Date(),
        url: await page.url()
      };

      // Performance thresholds (Lighthouse scores are 0-1)
      expect(metrics.performance).toBeGreaterThan(0.5); // At least 50%
      expect(metrics.seo).toBeGreaterThan(0.7); // At least 70% for SEO
      expect(metrics.accessibility).toBeGreaterThan(0.7); // At least 70% for accessibility

      // Log top failing audits if any
      const audits = runnerResult.lhr.audits;
      const failingAudits = Object.values(audits)
        .filter(audit => audit.score < 0.9 && audit.scoreDisplayMode === 'numeric')
        .sort((a, b) => a.score - b.score)
        .slice(0, 5);

      // Removed debug logging

    } catch (error) {
      console.error('Lighthouse audit failed:', error);
      throw error;
    }

    await page.close();
  });

  test('Core Web Vitals metrics', async ({ browser }) => {
    const page = await browser.newPage();
    await page.goto('/');

    // Monitor Web Vitals (simulated)
    const webVitals = await page.evaluate(() => {
      return new Promise((resolve) => {
        let CLSValue = 0;
        let FCPValue = 0;
        let LCPValue = 0;

        // Simulate basic measurements
        setTimeout(() => {
          resolve({
            CLS: CLSValue,
            FCP: FCPValue || performance.now(),
            LCP: LCPValue || performance.now()
          });
        }, 3000);
      });
    });



    await page.close();
  });
});
