# Mossé Luxe Multi-Layer Testing System

A comprehensive testing suite implementing Cypress + Percy + Postman + Pa11y + PageSpeed Insights for enterprise-quality e-commerce testing.

## 🔧 Components

- **🖥️ Cypress** → Full browser automation and E2E testing
- **📸 Percy** → Visual screenshot comparison and pixel-perfect testing
- **🔗 Postman** → API testing collection for backend endpoints
- **♿ Pa11y** → Accessibility compliance checking (WCAG 2.1 AA)
- **⚡ PageSpeed Insights** → Performance monitoring and grading

## 🚀 Quick Start

### Prerequisites
1. Node.js installed
2. Local server running at `http://localhost/mosseluxe`
3. Database and sample data configured

### Installation
```bash
npm install
```

### Run All Tests
```bash
npm test
# or
node tests/run-full-suite.js
```

### Individual Tests
```bash
# E2E Tests (Cypress + Percy)
npm run test:cypress

# Accessibility Tests (Pa11y)
npm run test:accessibility

# Performance Tests (PSI)
npm run test:performance

# Full Suite
npm run test:full-suite
```

## 📁 File Structure

```
tests/
├── cypress/
│   ├── e2e/
│   │   └── comprehensive.cy.js    # Main E2E tests with visual snapshots
│   └── support/
│       └── e2e.js                 # Cypress configuration and Percy integration
├── postman-collection.json        # API testing collection for Postman
├── accessibility-test.js          # Pa11y accessibility testing
├── performance-test.js            # PageSpeed Insights testing
├── run-full-suite.js              # Main test runner
└── README.md                      # This documentation
```

## 🧪 Test Details

### Cypress + Percy (E2E + Visual Regression)
- Full shopping flow testing
- Visual snapshots captured at key points
- Responsive design testing (Mobile/Desktop)
- Error handling validation

### Pa11y (Accessibility)
- WCAG 2.1 AA compliance checking
- Scans homepage, shop, cart, about, contact pages
- Generates detailed accessibility reports

### PageSpeed Insights (Performance)
- Mobile and desktop performance scoring
- SEO and accessibility scoring
- Best practices compliance
- Performance grading (A-F)

### Postman Collection (API)
- Products API testing (list, search, pagination, single product)
- Cart API testing (view, add, update, remove)
- JSON collection ready for Postman import

## 📊 Reports

All reports are saved to the `test-results/` directory:

- `accessibility-report.json` - Detailed accessibility issues
- `accessibility-summary.txt` - Quick accessibility overview
- `performance-report.json` - Detailed performance scores
- `performance-summary.txt` - Performance grading summary
- `final-test-suite-summary.txt` - Overall test suite results

## 🛠️ Configuration

### Environment Variables
```bash
# Base URL for testing (optional, defaults to localhost)
BASE_URL=http://localhost/mosseluxe

# Percy token for cloud visual regression (optional)
PERCY_TOKEN=your_percy_token_here

# Node environment
NODE_ENV=test
```

### Percy Setup (Optional)
For cloud-based visual regression comparison:

1. Create a [Percy](https://percy.io/) account
2. Get your project token
3. Set `PERCY_TOKEN` environment variable
4. Run tests - snapshots will be uploaded for comparison

### Postman API Testing
1. Import `tests/postman-collection.json` into Postman
2. Configure environment variables:
   - `baseUrl`: `http://localhost/mosseluxe`
   - `session_id`: Your PHP session cookie (optional for cart tests)
3. Run the collection manually or via Newman CLI

## 🔄 CI/CD Integration

Add to your CI pipeline:

```yaml
# Example GitHub Actions
- name: Run Full Test Suite
  run: |
    npm install
    npm test
  env:
    NODE_ENV: test
    PERCY_TOKEN: ${{ secrets.PERCY_TOKEN }}
```

## 📋 Test Status

| Component | Status | Description |
|-----------|--------|-------------|
| ✅ Cypress | Ready | E2E tests with visual snapshots |
| ✅ Percy | Configured | Visual regression integrated |
| ✅ Postman | Collection Ready | API tests prepared |
| ✅ Pa11y | Ready | Accessibility testing implemented |
| ✅ PSI | Ready | Performance monitoring active |

## 🚨 Troubleshooting

### Cypress Not Starting
- Ensure local server is running: `cd /path/to/mosseluxe && php -S localhost:80`
- Try running with GUI: `npm run cypress:open`

### Performance Tests Failing
- Ensure internet connection for PageSpeed Insights API
- Add delays if rate limiting occurs

### Percy Upload Failing
- Check `PERCY_TOKEN` is set correctly
- Ensure network allows connections to percy.io

### Postman Collection Variables
- Set `baseUrl` to your development URL
- Add `session_id` cookie for cart testing

## 📈 Next Steps

1. **Review Test Results**: Check all reports in `test-results/`
2. **Fix Issues**: Address any accessibility, performance, or functional problems
3. **CI/CD Setup**: Integrate into your deployment pipeline
4. **Percy Dashboard**: Review visual regression comparisons
5. **API Coverage**: Extend Postman collection as new endpoints are added

## 📞 Support

This testing suite provides enterprise-grade quality assurance for the Mossé Luxe e-commerce platform. All tools are industry standards for modern web development.

---

**Built with:** Cypress, Percy, Postman, Pa11y, Google PageSpeed Insights
