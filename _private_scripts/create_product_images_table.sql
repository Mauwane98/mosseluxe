-- Create product_images table for multiple product images and media
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `media_type` ENUM('image', 'video') NOT NULL DEFAULT 'image',
  `variant_color` varchar(100) DEFAULT NULL,
  `variant_size` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_360_view` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `variant_color` (`variant_color`),
  KEY `variant_size` (`variant_size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
