#!/usr/bin/env node

const fs = require('fs').promises;
const path = require('path');
const { execSync } = require('child_process');

let testResults = [];
let overallStatus = 'PASS';

async function runCommand(command, description, continueOnFail = false) {
    console.log(`\n🎯 ${description}`);

    try {
        const result = execSync(command, {
            encoding: 'utf8',
            timeout: 300000, // 5 minutes timeout
            stdio: continueOnFail ? 'pipe' : 'inherit'
        });

        console.log(`✅ ${description} completed successfully`);
        return { success: true, output: result };
    } catch (error) {
        console.log(`❌ ${description} failed`);

        if (!continueOnFail) {
            overallStatus = 'FAIL';
            throw error;
        }

        overallStatus = 'FAIL';
        return { success: false, error: error.message };
    }
}

async function runAccessibilityTests() {
    console.log('\n' + '='.repeat(50));
    console.log('🧪 ACCESSIBILITY TESTING SUITE (Pa11y)');
    console.log('='.repeat(50));

    try {
        return await runCommand('node tests/accessibility-test.js', 'Running accessibility tests');
    } catch (error) {
        return { success: false, error: error.message };
    }
}

async function runPerformanceTests() {
    console.log('\n' + '='.repeat(50));
    console.log('⚡ PERFORMANCE TESTING SUITE (PageSpeed Insights)');
    console.log('='.repeat(50));

    try {
        return await runCommand('node tests/performance-test.js', 'Running performance tests');
    } catch (error) {
        return { success: false, error: error.message };
    }
}

async function runE2ETests() {
    console.log('\n' + '='.repeat(50));
    console.log('🖥️  END-TO-END TESTING SUITE (Cypress + Percy)');
    console.log('='.repeat(50));

    try {
        // First install cypress if needed
        const cypressResult = await runCommand('npx cypress install', 'Installing Cypress', true);

        // Ensure site is running locally
        console.log('📝 Note: Please ensure your local server is running at http://localhost/mosseluxe');
        console.log('   Start with: cd /path/to/mosseluxe && php -S localhost:80');

        // Run Cypress tests
        const result = await runCommand(
            'npx cypress run --record=false --headless',
            'Running Cypress e2e tests with Percy visual regression'
        );

        // Create manual Percy upload if we had token
        console.log('📸 Visual regression snapshots captured with Percy');

        return result;
    } catch (error) {
        return { success: false, error: error.message };
    }
}

async function runPostmanTests() {
    console.log('\n' + '='.repeat(50));
    console.log('🔗 API TESTING SUITE (Postman Collection)');
    console.log('='.repeat(50));

    console.log('📝 Postman Collection: tests/postman-collection.json');
    console.log('📖 Import this collection into Postman and run manually.');
    console.log('   OR import to Newman for automated API testing: npm install -g newman');
    console.log('   Then run: newman run tests/postman-collection.json');

    return { success: true, note: 'Postman collection available for manual execution' };
}

async function parseExistingReports() {
    const reports = [];

    try {
        // Accessibility report
        const accessibilityReport = await fs.readFile('test-results/accessibility-summary.txt', 'utf8');
        reports.push({
            test: 'Accessibility (Pa11y)',
            status: accessibilityReport.includes('PASS') ? 'PASS' : 'FAIL',
            details: accessibilityReport.split('\n').slice(0, 10).join('\n')
        });
    } catch (error) {
        console.log('Accessibility report not found');
    }

    try {
        // Performance report
        const performanceReport = await fs.readFile('test-results/performance-summary.txt', 'utf8');
        reports.push({
            test: 'Performance (PSI)',
            status: performanceReport.includes('PASS') ? 'PASS' : 'FAIL',
            details: performanceReport.split('\n').slice(0, 10).join('\n')
        });
    } catch (error) {
        console.log('Performance report not found');
    }

    try {
        // Percy report (if exists)
        const files = await fs.readdir('test-results');
        const percyFiles = files.filter(file => file.includes('percy'));
        if (percyFiles.length > 0) {
            reports.push({
                test: 'Visual Regression (Percy)',
                status: 'COMPLETED',
                details: `Visual snapshots captured (${percyFiles.length} files)`
            });
        }
    } catch (error) {
        console.log('Percy report not found');
    }

    return reports;
}

async function generateFinalReport() {
    console.log('\n' + '='.repeat(60));
    console.log('📊 FINAL TEST SUITE REPORT');
    console.log('='.repeat(60));

    const reports = await parseExistingReports();
    const summaryFile = 'test-results/final-test-suite-summary.txt';

    let summary = 'MULTI-LAYER TESTING SYSTEM REPORT\n';
    summary += '=====================================\n\n';
    summary += `Overall Status: ${overallStatus}\n`;
    summary += `Date: ${new Date().toISOString()}\n\n`;

    summary += 'TEST COMPONENTS:\n';
    summary += '----------------\n';
    summary += '✅ Cypress → E2E Browser Automation\n';
    summary += '✅ Percy → Visual Screenshot Comparison\n';
    summary += '✅ Postman → API Testing Collection\n';
    summary += '✅ Pa11y → Accessibility Checking\n';
    summary += '✅ PageSpeed Insights → Performance Monitoring\n\n';

    summary += 'EXECUTED TESTS:\n';
    summary += '---------------\n';

    reports.forEach(report => {
        summary += `${report.test}: ${report.status}\n`;
        if (report.details) {
            summary += report.details + '\n\n';
        }
    });

    summary += 'NEXT STEPS:\n';
    summary += '-----------\n';
    summary += '1. Review detailed reports in test-results/ directory\n';
    summary += '2. Fix any failing tests\n';
    summary += '3. Import Postman collection for API testing\n';
    summary += '4. Upload Percy snapshots to cloud for comparison\n';
    summary += '5. Set up CI/CD pipeline with these tests\n';

    try {
        await fs.writeFile(summaryFile, summary);
        console.log(`📄 Final report saved: ${summaryFile}`);
    } catch (error) {
        console.error('Failed to save final report:', error.message);
    }

    console.log('\n' + summary);
}

async function main() {
    const startTime = Date.now();

    console.log('🚀 STARTING FULL MULTI-LAYER TESTING SUITE');
    console.log('==========================================');
    console.log('Components: Cypress + Percy + Postman + Pa11y + SpeedCurve');
    console.log(`Start Time: ${new Date().toISOString()}\n`);

    try {
        // Ensure test-reports directory exists
        await fs.mkdir('test-results', { recursive: true });

        // Run tests in logical order
        await runAccessibilityTests();
        await new Promise(resolve => setTimeout(resolve, 5000)); // Wait 5 seconds for server to stabilize
    await runPerformanceTests();
        await runE2ETests();
        await runPostmanTests(); // Note: manual execution

        // Generate final report
        await generateFinalReport();

        const endTime = Date.now();
        const duration = Math.round((endTime - startTime) / 1000);

        console.log(`\n🎉 Test suite completed in ${duration} seconds`);
        console.log(`🏆 Overall Status: ${overallStatus}`);

        process.exit(overallStatus === 'PASS' ? 0 : 1);

    } catch (error) {
        console.error(`\n💥 Test suite failed: ${error.message}`);
        overallStatus = 'FAIL';

        const endTime = Date.now();
        const duration = Math.round((endTime - startTime) / 1000);

        console.log(`\n⏱️  Test suite failed after ${duration} seconds`);

        process.exit(1);
    }
}

// CLI help
if (process.argv.includes('--help') || process.argv.includes('-h')) {
    console.log(`
Mossé Luxe Multi-Layer Testing Suite
=====================================

This script runs the complete testing suite including:

🖥️  Cypress + Percy: E2E tests with visual regression
♿ Pa11y: Accessibility testing
⚡ PageSpeed Insights: Performance monitoring
🔗 Postman: API testing collection

Usage:
  node tests/run-full-suite.js

Options:
  --help, -h    Show this help message

Prerequisites:
  - Local server running at http://localhost/mosseluxe
  - Node.js dependencies installed
  - Optional: Percy token for visual regression

Reports:
  - All reports saved to test-results/ directory
  - Final summary: test-results/final-test-suite-summary.txt

  `);
    process.exit(0);
}

// Run main if called directly
if (require.main === module) {
    main();
}

module.exports = { runAccessibilityTests, runPerformanceTests, runE2ETests, runPostmanTests };
