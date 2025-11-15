-- Add badge-related columns to products table
ALTER TABLE products ADD COLUMN is_coming_soon TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN is_bestseller TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN is_new TINYINT(1) DEFAULT 0;
