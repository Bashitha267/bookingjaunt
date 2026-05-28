-- Database Creation and Seeding script for Bookingjaunt Homepage Customization
-- Copy and paste this directly into phpMyAdmin SQL console

-- 1. Create homepage_vibe_grid table (THE SOUL OF SRI LANKA)
CREATE TABLE IF NOT EXISTS `homepage_vibe_grid` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `badge` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `media_path` VARCHAR(255) NOT NULL,
    `media_type` ENUM('image', 'video') DEFAULT 'image',
    `link_url` VARCHAR(255) NOT NULL,
    `accent_color` VARCHAR(50) DEFAULT '#10b981',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default values for homepage_vibe_grid
INSERT INTO `homepage_vibe_grid` (`title`, `badge`, `description`, `media_path`, `media_type`, `link_url`, `accent_color`, `sort_order`, `is_active`) VALUES
('Coastal Serenity', '01 / Beaches', 'Swaying palm trees, golden sun-kissed beaches, and pristine tropical waters waiting to be explored.', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=700&q=80', 'image', 'hotels.php?q=galle', '#10b981', 1, 1),
('Misty Highlands', '02 / Hills', 'Deep emerald hills, endless tea plantations, and scenic train rides floating through the morning mist.', 'https://images.unsplash.com/photo-1545249390-6bdfa286032f?auto=format&fit=crop&w=700&q=80', 'image', 'hotels.php?q=kandy', '#febb02', 2, 1),
('Ancient Legacy', '03 / Culture', 'Sacred temples, lost cities, and majestic rock fortresses whispering tales of an illustrious past.', 'https://images.unsplash.com/photo-1578590471398-29eead690a2a?auto=format&fit=crop&w=700&q=80', 'image', 'hotels.php?q=sigiriya', '#60a5fa', 3, 1);


-- 2. Create homepage_showcase_items table (CURATED ISLAND EXPERIENCES)
CREATE TABLE IF NOT EXISTS `homepage_showcase_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `media_path` VARCHAR(255) NOT NULL,
    `media_type` ENUM('image', 'video') DEFAULT 'image',
    `link_url` VARCHAR(255) NOT NULL,
    `accent_color` VARCHAR(50) DEFAULT '#10b981',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default values for homepage_showcase_items
INSERT INTO `homepage_showcase_items` (`title`, `subtitle`, `description`, `media_path`, `media_type`, `link_url`, `accent_color`, `sort_order`, `is_active`) VALUES
('Encounter the Majestic Wild', 'Wildlife & Conservation', 'Sri Lanka hosts one of the highest rates of biological endemism in the world. Experience the unique thrill of spotting leopards in Yala, seeing giants gather in Minneriya, and watching blue whales migrate in Mirissa.', 'https://images.unsplash.com/photo-1456926631375-92c8ce872def?auto=format&fit=crop&w=850&q=80', 'image', 'hotels.php?q=safari', '#10b981', 1, 1),
('Rejuvenate with Ancient Ayurveda', 'Wellness & Healing', 'Restore complete harmony to your body, mind, and spirit. Our hand-picked collection of wellness resorts offers authentic Ayurvedic treatments, herbal baths, and daily meditation sessions set in serene forest sanctuaries.', 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=850&q=80', 'image', 'hotels.php?type=resort', '#006ce4', 2, 1);
