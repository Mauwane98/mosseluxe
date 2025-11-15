CREATE TABLE IF NOT EXISTS `homepage_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(50) NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `content` text,
  `button_text` varchar(100) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `background_color` varchar(20) DEFAULT NULL,
  `text_color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `homepage_sections` (`section_key`, `section_name`, `title`, `subtitle`, `content`, `button_text`, `button_url`, `is_active`, `sort_order`) VALUES
('hero_carousel', 'Hero Carousel', 'Welcome to Mossé Luxe', 'Luxury Streetwear Inspired by Legacy', 'Discover our curated collection of premium streetwear, crafted with precision and passion.', 'Shop Now', '#new-arrivals', 1, 1),
('new_arrivals', 'New Arrivals', 'New Arrivals', 'Latest Collection', 'Check out our newest pieces, featuring the latest trends in luxury streetwear.', NULL, NULL, 1, 2),
('brand_statement', 'Brand Statement', 'Luxury Inspired by Legacy', 'Our Philosophy', 'We define luxury not by price, but by quality, craftsmanship, and timeless design. Each piece is a modern expression of a timeless legacy, blending our rich history with a style for today\'s world.', 'Read Our Story', 'about.php', 1, 3),
('newsletter', 'Newsletter Signup', 'Join the List', 'Stay Connected', 'Be the first to know about new drops, exclusive events, and insider-only deals.', 'Subscribe', NULL, 1, 4);
