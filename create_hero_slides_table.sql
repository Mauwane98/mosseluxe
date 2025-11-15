CREATE TABLE IF NOT EXISTS `hero_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_url` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `hero_slides` (`id`, `image_url`, `title`, `subtitle`, `button_text`, `button_url`, `is_active`, `sort_order`) VALUES
(1, 'assets/images/hero2.png', 'Explore Our Collection', NULL, 'Explore Our Collection', '#new-arrivals', 1, 10),
(2, 'assets/images/hero1.png', 'The Art of Luxe', NULL, 'Shop Mossé Luxe', '#new-arrivals', 1, 20),
(3, 'assets/images/hero.jpeg', 'New Season, New Style', NULL, 'Discover More', '#new-arrivals', 1, 30);

-- Add a link to the new hero management page in the admin sidebar
INSERT IGNORE INTO `homepage_sections` (`section_key`, `section_name`, `title`, `is_active`, `sort_order`) VALUES
('manage_hero', 'Manage Hero', 'Manage Hero Carousel', 0, 99); -- is_active=0 because it's a link, not a display section