const { chromium } = require('playwright');

async function runLighthouse(siteUrl) {
  const lighthouse = (await import('lighthouse')).default;

  const browser = await chromium.launch({ args: ["--remote-debugging-port=9222"] });

  const result = await lighthouse(siteUrl, {
    port: 9222,
    output: "json",
    logLevel: "error",
  });

  await browser.close();

  const data = JSON.parse(result.report);

  return {
    performance: data.categories.performance.score * 100,
    accessibility: data.categories.accessibility.score * 100,
    seo: data.categories.seo.score * 100,
    bestPractices: data.categories["best-practices"].score * 100,
  };
}

module.exports = { runLighthouse };
