# Mossé Luxe - E-commerce Website

## Overview
Mossé Luxe is a premium e-commerce platform specializing in luxury streetwear and fashion accessories, crafted with precision and heritage. The platform features a modern, mobile-first design with comprehensive admin functionality.

## Features

### Frontend
- **Product Management**: Dynamic product catalog with categories, filters, and search
- **Shopping Cart**: Persistent cart functionality with session merging for users
- **Checkout Process**: Secure payment integration with Yoco and PayFast gateways
- **User Accounts**: Registration, login, account management, and order history
- **WhatsApp Integration**: Customer support chat functionality
- **Responsive Design**: Mobile-first approach with dark/light theme support
- **SEO Optimized**: Dynamic meta tags, sitemap, and structured data

### Admin Panel
- **Dashboard**: Sales analytics, low stock alerts, and recent activity overview
- **Product Management**: CRUD operations with image upload and variants
- **Order Management**: Order processing, status updates, and customer communication
- **User Management**: Customer account management and admin role handling
- **Content Management**: Dynamic hero slides, homepage sections, and announcements
- **Export Functionality**: Sales reports and customer/order data export
- **Settings Management**: Store configuration and WhatsApp integration settings

## Technical Stack
- **Backend**: PHP 8.1+, MySQL 8.0+
- **Frontend**: TailwindCSS, JavaScript (ES6+)
- **Security**: CSRF protection, input sanitization, SQL injection prevention
- **Payment**: Yoco payment gateway integration
- **Caching**: Static asset caching with .htaccess optimization
- **Session Management**: Secure session handling with database persistence

## Installation

1. **Prerequisites**
   - PHP 8.1 or higher
   - MySQL 8.0 or higher
   - Apache/Nginx web server
   - SSL certificate (recommended for production)

2. **Setup Steps**
   ```bash
   # Clone the repository
   git clone https://github.com/yourusername/mosse-luxe.git
   cd mosse-luxe

   # Install dependencies
   composer install
   npm install

   # Set up environment variables
   cp .env.example .env
   # Configure database credentials, SMTP settings, and payment gateway keys

   # Create database
   php _private_scripts/db_setup.php

   # Seed initial data
   php _private_scripts/seed_database.php
   ```

3. **Production Deployment**
   ```bash
   # Install production dependencies
   composer install --no-dev --optimize-autoloader
   npm run build

   # Run database migrations and index optimization
   php add_db_indexes.php

   # Set proper file permissions
   chmod 755 logs/
   chmod 644 logs/*.log
   ```

## Security Features
- CSRF token validation on all forms
- Prepared statements for all database queries
- Input sanitization and validation
- Session security with secure cookies
- Rate limiting for sensitive operations
- Error logging with sanitized data

## Performance Optimizations
- Static asset caching (1 year for images/CSS/JS)
- Database query optimization with proper indexing
- Lazy loading for images
- Minified CSS and JavaScript
- Gzip compression enabled

## Production Checklist
- [ ] Environment variables configured (.env file)
- [ ] SSL certificate installed and configured
- [ ] Database connection tested and secured
- [ ] SMTP settings configured for email notifications
- [ ] Payment gateway credentials set up
- [ ] File permissions properly configured
- [ ] Error logging directory created and secured
- [ ] Database indexes optimized (run `add_db_indexes.php`)
- [ ] Production error pages configured (404.php, 500.php)

## Maintenance
- Regular database backups (recommended daily)
- Monitor error logs in `/logs/` directory
- Update dependencies regularly via Composer/NPM
- Review and update SSL certificates annually
- Monitor payment gateway integration health

## License
Copyright Mossé Luxe - All Rights Reserved

## Support
For technical support or questions, please contact the development team.
