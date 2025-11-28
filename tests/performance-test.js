const lighthouse = (...args) => import('lighthouse').then(mod => mod.default(...args));
const chromeLauncher = import('chrome-launcher');
const fs = require('fs').promises;

const BASE_URL = process.env.BASE_URL || 'http://localhost/mosseluxe';

const testPages = [
    { url: `${BASE_URL}/`, name: 'Homepage' },
    { url: `${BASE_URL}/shop.php`, name: 'Shop Page' },
    { url: `${BASE_URL}/cart.php`, name: 'Cart Page' },
    { url: `${BASE_URL}/product-details.php?id=1`, name: 'Product Details' }
];

let results = [];

async function runLighthouseForPage(page, strategy) {
    let chrome;
    try {
        const launcher = await chromeLauncher;
        chrome = await launcher.launch({ chromeFlags: ['--headless'] });
        const options = { logLevel: 'info', output: 'json', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'], port: chrome.port };
        const runnerResult = await lighthouse(page.url, options);

        const pageResult = {
            page: page.name,
            url: page.url,
            performance: runnerResult.lhr.categories.performance.score,
            accessibility: runnerResult.lhr.categories.accessibility.score,
            seo: runnerResult.lhr.categories.seo.score,
            bestPractices: runnerResult.lhr.categories['best-practices'].score,
            scores: runnerResult.lhr.categories,
            device: strategy,
            time: new Date().toISOString()
        };

        results.push(pageResult);

    } catch (error) {
        console.error(`✗ Failed to test ${page.name} (${strategy}):`, error.message);
        results.push({ page: page.name, url: page.url, error: error.message, device: strategy });
    } finally {
        if (chrome) {
            await chrome.kill();
        }
    }
}

async function runAllTestsForStrategy(strategy) {
    for (const page of testPages) {
        await runLighthouseForPage(page, strategy);
        await new Promise(resolve => setTimeout(resolve, 1000));
    }
}

async function generateReport() {
    const reportPath = 'test-results/performance-report.json';
    const summaryReportPath = 'test-results/performance-summary.txt';

    try {
        await fs.mkdir('test-results', { recursive: true });
    } catch (error) {
        // Directory might already exist
    }

    await fs.writeFile(reportPath, JSON.stringify(results, null, 2));

    let summaryText = '⚡ PERFORMANCE TEST RESULTS (Lighthouse)\n';
    summaryText += '='.repeat(60) + '\n\n';

    const mobileResults = results.filter(r => r.device === 'mobile');
    const desktopResults = results.filter(r => r.device === 'desktop');

    summaryText += '📱 MOBILE PERFORMANCE:\n';
    summaryText += '-'.repeat(30) + '\n';

    mobileResults.forEach(result => {
        if (result.performance) {
            summaryText += `${result.page}:\n`;
            summaryText += `  Performance: ${(result.performance * 100).toFixed(1)}%\n`;
            summaryText += `  SEO: ${(result.seo * 100).toFixed(1)}%\n`;
            summaryText += `  Accessibility: ${(result.accessibility * 100).toFixed(1)}%\n`;
            summaryText += `  Best Practices: ${(result.bestPractices * 100).toFixed(1)}%\n\n`;
        } else {
            summaryText += `${result.page}: Failed to load\n\n`;
        }
    });

    summaryText += '💻 DESKTOP PERFORMANCE:\n';
    summaryText += '-'.repeat(30) + '\n';

    desktopResults.forEach(result => {
        if (result.performance) {
            summaryText += `${result.page} (Desktop):\n`;
            summaryText += `  Performance: ${(result.performance * 100).toFixed(1)}%\n\n`;
        } else {
            summaryText += `${result.page}: Failed to load\n\n`;
        }
    });

    const validMobile = mobileResults.filter(r => r.performance);
    const avgPerformance = validMobile.reduce((sum, r) => sum + r.performance, 0) / validMobile.length;
    const avgSEO = validMobile.reduce((sum, r) => sum + r.seo, 0) / validMobile.length;
    const avgAccessibility = validMobile.reduce((sum, r) => sum + r.accessibility, 0) / validMobile.length;

    summaryText += `AVERAGES:\n`;
    summaryText += `  Mobile Performance: ${(avgPerformance * 100).toFixed(1)}%\n`;
    summaryText += `  Mobile SEO: ${(avgSEO * 100).toFixed(1)}%\n`;
    summaryText += `  Mobile Accessibility: ${(avgAccessibility * 100).toFixed(1)}%\n`;

    const getGrade = (score) => {
        if (score >= 0.9) return 'A (Excellent)';
        if (score >= 0.7) return 'B (Good)';
        if (score >= 0.5) return 'C (Needs Work)';
        if (score >= 0.3) return 'D (Poor)';
        return 'F (Very Poor)';
    };

    summaryText += `\nGRADES:\n`;
    if (avgPerformance > 0) {
        summaryText += `  Performance Grade: ${getGrade(avgPerformance)}\n`;
        summaryText += `  SEO Grade: ${getGrade(avgSEO)}\n`;
        summaryText += `  Accessibility Grade: ${getGrade(avgAccessibility)}\n`;
    }

    await fs.writeFile(summaryReportPath, summaryText);

    return { avgPerformance, avgSEO, avgAccessibility };
}

async function main() {
    try {
        await runAllTestsForStrategy('mobile');
        await runAllTestsForStrategy('desktop');
        const stats = await generateReport();

        if (stats.avgPerformance < 0.5) {
            process.exit(1);
        } else {
            process.exit(0);
        }
    } catch (error) {
        console.error('Fatal error:', error);
        process.exit(1);
    }
}

if (require.main === module) {
    main();
}

module.exports = { runAllTestsForStrategy, generateReport };
