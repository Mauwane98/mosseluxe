-- Create new_arrivals table for managing homepage new arrivals section
CREATE TABLE IF NOT EXISTS `new_arrivals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 1,
  `release_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`),
  KEY `display_order` (`display_order`),
  KEY `release_date` (`release_date`),
  CONSTRAINT `fk_new_arrivals_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings for new arrivals
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('new_arrivals_message', 'New arrivals will be available soon. Please check back later.'),
('new_arrivals_display_count', '4')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
