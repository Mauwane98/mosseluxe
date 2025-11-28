const pa11y = require('pa11y');
const fs = require('fs').promises;

const BASE_URL = process.env.BASE_URL || 'http://localhost/mosseluxe';

const testPages = [
    { url: `${BASE_URL}/`, name: 'Homepage' },
    { url: `${BASE_URL}/shop.php`, name: 'Shop Page' },
    { url: `${BASE_URL}/cart.php`, name: 'Cart Page' },
    { url: `${BASE_URL}/about.php`, name: 'About Page' },
    { url: `${BASE_URL}/contact.php`, name: 'Contact Page' }
];

let results = [];

async function runPa11yTests() {

    for (const page of testPages) {

        try {
            const Pa11yResult = await pa11y(page.url, {
                // Standard WCAG 2.1 AA rules
                standard: 'WCAG2AA',
                // Don't fail on warnings
                includeWarnings: false,
                // Include notices
                includeNotices: false,
                // Wait for page load
                timeout: 30000,
                // Browser settings
                log: {
                    debug: () => {}, // Disable debug logs
                    error: console.error,
                    info: console.log
                }
            });

            const pageResult = {
                page: page.name,
                url: page.url,
                issues: Pa11yResult.issues,
                errorCount: Pa11yResult.issues.length,
                critical: Pa11yResult.issues.filter(i => i.type === 'error').length,
                warnings: Pa11yResult.issues.filter(i => i.type === 'warning').length
            };

            results.push(pageResult);


        } catch (error) {
            console.error(`✗ Failed to test ${page.name}:`, error.message);
            results.push({
                page: page.name,
                url: page.url,
                error: error.message,
                errorCount: 0,
                issues: []
            });
        }
    }

    return results;
}

async function generateReport() {
    const reportPath = 'test-results/accessibility-report.json';
    const summaryReportPath = 'test-results/accessibility-summary.txt';

    // Create test-results directory if it doesn't exist
    try {
        await fs.mkdir('test-results', { recursive: true });
    } catch (error) {
        // Directory might already exist
    }

    // Generate JSON report
    await fs.writeFile(reportPath, JSON.stringify(results, null, 2));

    // Generate summary report
    let summaryText = '♿ ACCESSIBILITY TEST RESULTS\n';
    summaryText += '=' .repeat(50) + '\n\n';

    const totalIssues = results.reduce((sum, result) => sum + result.errorCount, 0);
    const totalCritical = results.reduce((sum, result) => sum + (result.critical || 0), 0);
    const totalWarnings = results.reduce((sum, result) => sum + (result.warnings || 0), 0);

    summaryText += `Total Issues: ${totalIssues}\n`;
    summaryText += `Critical Issues: ${totalCritical}\n`;
    summaryText += `Warnings: ${totalWarnings}\n\n`;

    results.forEach(result => {
        summaryText += `${result.page}:\n`;
        summaryText += `  Issues: ${result.errorCount}\n`;
        if (result.errorCount > 0 && result.issues) {
            result.issues.slice(0, 5).forEach(issue => {
                summaryText += `  - ${issue.type}: ${issue.message}\n`;
            });
            if (result.issues.length > 5) {
                summaryText += `  ... and ${result.issues.length - 5} more\n`;
            }
        }
        summaryText += '\n';
    });

    await fs.writeFile(summaryReportPath, summaryText);

    const passFail = totalCritical === 0 ? '✅ PASS - No critical accessibility issues' : '❌ FAIL - Critical accessibility issues found';

    return { totalIssues, totalCritical, totalWarnings };
}

// Main execution
async function main() {
    try {
        await runPa11yTests();
        const stats = await generateReport();

        // Exit with appropriate code
        if (stats.totalCritical > 0) {
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

module.exports = { runPa11yTests, generateReport };
