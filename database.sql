-- Create Database
CREATE DATABASE IF NOT EXISTS newspower_db;
USE newspower_db;

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Posts/News table
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    category VARCHAR(100),
    tags TEXT,
    author_id INT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    shares_count INT DEFAULT 0,
    meta_title VARCHAR(200),
    meta_description VARCHAR(300),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (author_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
);

-- Pages table (for About, Contact, etc.)
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    page_template ENUM('default', 'about', 'contact') DEFAULT 'default',
    meta_title VARCHAR(200),
    meta_description VARCHAR(300),
    status ENUM('draft', 'published') DEFAULT 'published',
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
);

-- Site settings table
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'textarea', 'number', 'boolean', 'image', 'json') DEFAULT 'text',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    admin_reply TEXT NULL,
    replied_by INT NULL,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (replied_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Page views tracking
CREATE TABLE page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_type ENUM('post', 'page', 'home') NOT NULL,
    page_id INT NULL,
    page_url VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer VARCHAR(500),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_type_id (page_type, page_id),
    INDEX idx_viewed_at (viewed_at)
);

-- Social shares tracking
CREATE TABLE social_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    platform ENUM('facebook', 'twitter', 'linkedin', 'whatsapp', 'copy') NOT NULL,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post_platform (post_id, platform)
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password is 'admin123' - CHANGE THIS!)
INSERT INTO admin_users (username, email, password, full_name, role) VALUES 
('admin', 'whisper2king@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Site Administrator', 'super_admin');

-- Insert site settings
INSERT INTO site_settings (setting_name, setting_value, setting_type, category, description) VALUES 
('site_name', 'Your Website', 'text', 'general', 'Website name'),
('site_description', 'A modern website built with PHP and MySQL', 'textarea', 'general', 'Website description'),
('site_logo', '', 'image', 'general', 'Website logo'),
('contact_email', 'whisper2king@gmail.com', 'text', 'contact', 'Contact email address'),
('contact_phone', '', 'text', 'contact', 'Contact phone number'),
('contact_address', '', 'textarea', 'contact', 'Contact address'),
('social_facebook', '', 'text', 'social', 'Facebook URL'),
('social_twitter', '', 'text', 'social', 'Twitter URL'),
('social_linkedin', '', 'text', 'social', 'LinkedIn URL'),
('social_instagram', '', 'text', 'social', 'Instagram URL'),
('posts_per_page', '10', 'number', 'general', 'Number of posts per page'),
('enable_comments', '1', 'boolean', 'general', 'Enable comments on posts'),
('maintenance_mode', '0', 'boolean', 'general', 'Enable maintenance mode'),
('google_analytics', '', 'textarea', 'seo', 'Google Analytics code'),
('meta_keywords', '', 'textarea', 'seo', 'Default meta keywords'),
('home_hero_title', 'Welcome to Our Website', 'text', 'homepage', 'Hero section title'),
('home_hero_subtitle', 'Discover amazing content and stay updated with the latest news', 'textarea', 'homepage', 'Hero section subtitle'),
('home_hero_image', '', 'image', 'homepage', 'Hero section background image');

-- Insert default pages
INSERT INTO pages (title, slug, content, page_template, meta_title, meta_description) VALUES 
('About Us', 'about', '<h2>About Our Company</h2><p>Welcome to our website. We are dedicated to providing quality content and services to our visitors.</p><p>Edit this content from the admin panel to tell your story.</p>', 'about', 'About Us - Your Website', 'Learn more about our company and what we do'),
('Contact Us', 'contact', '<h2>Get In Touch</h2><p>We would love to hear from you. Contact us using the form below or reach out to us directly.</p>', 'contact', 'Contact Us - Your Website', 'Get in touch with us for any inquiries');

-- Insert default categories
INSERT INTO categories (name, slug, description, color) VALUES 
('News', 'news', 'Latest news and updates', '#007bff'),
('Technology', 'technology', 'Technology related posts', '#28a745'),
('Business', 'business', 'Business and industry news', '#ffc107'),
('General', 'general', 'General posts and articles', '#6c757d');

-- Insert sample posts
INSERT INTO posts (title, slug, content, excerpt, category, author_id, status, is_featured, meta_title, meta_description, published_at) VALUES 
('Welcome to Your New Website', 'welcome-to-your-new-website', 
'<h2>Welcome to Your New Website</h2><p>This is your first post! You can edit or delete this post from your admin dashboard.</p><p>Your website is now ready and you can start adding your own content. Here are some things you can do:</p><ul><li>Create new posts and pages</li><li>Customize your site settings</li><li>Upload images and media</li><li>View analytics and visitor statistics</li></ul><p>Get started by logging into your admin dashboard and exploring all the features available to you.</p>', 
'Welcome to your new website. This is your first post that you can edit or delete from the admin dashboard.', 
'General', 1, 'published', TRUE, 
'Welcome to Your New Website', 
'Welcome to your new website. Get started with creating content and managing your site.', 
NOW()),

('Getting Started Guide', 'getting-started-guide',
'<h2>Getting Started with Your Website</h2><p>This guide will help you get familiar with managing your website content.</p><h3>Admin Dashboard</h3><p>Access your admin dashboard to manage all aspects of your website including posts, pages, and settings.</p><h3>Creating Content</h3><p>You can create new posts and pages easily from the admin interface. All content supports rich text editing.</p><h3>Managing Settings</h3><p>Customize your website settings, including site name, contact information, and social media links.</p>',
'A comprehensive guide to help you get started with managing your new website.',
'General', 1, 'published', FALSE,
'Getting Started Guide - Your Website',
'Learn how to manage and customize your new website with this comprehensive guide.',
NOW());