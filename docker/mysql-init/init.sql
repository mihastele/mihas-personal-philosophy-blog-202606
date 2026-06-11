CREATE DATABASE IF NOT EXISTS mihas_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mihas_blog;

CREATE TABLE IF NOT EXISTS admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    cover_image VARCHAR(512) DEFAULT NULL,
    language VARCHAR(5) NOT NULL DEFAULT 'en',
    post_type ENUM('markdown', 'html') NOT NULL DEFAULT 'markdown',
    custom_dir VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_slug_lang (slug, language),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_language (language)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(64) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(64) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES ('blog_title_en', 'Miha''s Blog of Philosophy') ON DUPLICATE KEY UPDATE setting_value = setting_value;
INSERT INTO settings (setting_key, setting_value) VALUES ('blog_tagline_en', 'Contemplations on existence, reason, and the human condition') ON DUPLICATE KEY UPDATE setting_value = setting_value;
INSERT INTO settings (setting_key, setting_value) VALUES ('blog_title_sl', 'Mihov blog o filozofiji') ON DUPLICATE KEY UPDATE setting_value = setting_value;
INSERT INTO settings (setting_key, setting_value) VALUES ('blog_tagline_sl', 'Razmišljanja o obstoju, razumu in človeškem stanju') ON DUPLICATE KEY UPDATE setting_value = setting_value;
INSERT INTO settings (setting_key, setting_value) VALUES ('default_language', 'en') ON DUPLICATE KEY UPDATE setting_value = setting_value;
INSERT INTO settings (setting_key, setting_value) VALUES ('available_languages', 'en,sl') ON DUPLICATE KEY UPDATE setting_value = setting_value;
