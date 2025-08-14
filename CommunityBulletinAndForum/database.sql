
-- Enhanced Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    post_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Enhanced Posts Table
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Comments Table (unchanged but included for completeness)
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Post Likes Table
CREATE TABLE post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_like (post_id, user_id)
);

-- Events Table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Trending Topics Table
CREATE TABLE trending_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic VARCHAR(100) NOT NULL UNIQUE,
    post_count INT DEFAULT 1,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, description, icon, color) VALUES
('Community Bulletins', 'Important announcements and community news', 'fas fa-bullhorn', '#007bff'),
('General Discussion', 'Open discussions about any topic', 'fas fa-comments', '#28a745'),
('Local Events', 'Community events and gatherings', 'fas fa-calendar-alt', '#fd7e14'),
('Help & Support', 'Get help from community members', 'fas fa-question-circle', '#dc3545'),
('Buy & Sell', 'Marketplace for community members', 'fas fa-shopping-cart', '#6f42c1'),
('Social Corner', 'Casual conversations and social interactions', 'fas fa-users', '#20c997');

-- Insert sample users for testing
INSERT INTO users (username, email, password, created_at) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW()),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW()),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- Insert sample posts for testing categories
INSERT INTO posts (user_id, category_id, title, content, excerpt, created_at) VALUES
(1, 1, 'Welcome to Our Community!', 'This is our first community bulletin. We are excited to have you here and look forward to building a great community together. #welcome #community #newmembers', 'Welcome message for our new community platform.', NOW() - INTERVAL 2 DAY),
(2, 2, 'What are your favorite hobbies?', 'I am curious to know what hobbies everyone enjoys in their free time. Please share your interests and maybe we can find some common ground! #hobbies #interests #discussion', 'Discussion about hobbies and interests in the community.', NOW() - INTERVAL 1 DAY),
(3, 3, 'Community BBQ This Weekend', 'Join us for a community BBQ this Saturday at the local park. Bring your family and friends for food, games, and great conversation! #bbq #weekend #community #events', 'Community BBQ event announcement for this weekend.', NOW() - INTERVAL 3 HOUR),
(1, 4, 'How to reset your password', 'If you are having trouble logging in, here is a step-by-step guide on how to reset your password and regain access to your account. #help #password #login #support', 'Password reset help guide for community members.', NOW() - INTERVAL 5 HOUR),
(2, 5, 'Selling: Vintage Guitar', 'I have a beautiful vintage acoustic guitar that I am looking to sell. It is in excellent condition and comes with a case. Message me if interested! #forsale #guitar #vintage #music', 'Vintage guitar for sale in excellent condition.', NOW() - INTERVAL 8 HOUR),
(3, 6, 'Coffee meetup anyone?', 'Would anyone be interested in meeting up for coffee this week? I know a great little cafe downtown that would be perfect for getting to know each other better. #coffee #meetup #social #downtown', 'Coffee meetup invitation for community members.', NOW() - INTERVAL 12 HOUR);

-- Insert sample trending topics based on the hashtags in posts
INSERT INTO trending_topics (topic, post_count, last_updated) VALUES
('community', 3, NOW() - INTERVAL 1 HOUR),
('welcome', 1, NOW() - INTERVAL 2 DAY),
('hobbies', 1, NOW() - INTERVAL 1 DAY),
('events', 1, NOW() - INTERVAL 3 HOUR),
('help', 1, NOW() - INTERVAL 5 HOUR),
('forsale', 1, NOW() - INTERVAL 8 HOUR),
('meetup', 1, NOW() - INTERVAL 12 HOUR),
('weekend', 1, NOW() - INTERVAL 3 HOUR);

-- Insert sample events for testing
INSERT INTO events (title, description, event_date, created_by, created_at) VALUES
('Community BBQ', 'Join us for a fun community barbecue at the local park. Food, games, and great company!', DATE_ADD(NOW(), INTERVAL 3 DAY) + INTERVAL 14 HOUR, 1, NOW() - INTERVAL 1 DAY),
('Book Club Meeting', 'Monthly book club discussion. This month we are reading "The Great Gatsby".', DATE_ADD(NOW(), INTERVAL 5 DAY) + INTERVAL 19 HOUR, 2, NOW() - INTERVAL 2 DAY),
('Neighborhood Cleanup', 'Help keep our community beautiful! Bring gloves and a positive attitude.', DATE_ADD(NOW(), INTERVAL 7 DAY) + INTERVAL 9 HOUR, 3, NOW() - INTERVAL 3 DAY),
('Coffee & Chat', 'Casual morning coffee meetup at the community center.', DATE_ADD(NOW(), INTERVAL 10 DAY) + INTERVAL 10 HOUR, 1, NOW() - INTERVAL 4 DAY),
('Game Night', 'Board games, card games, and fun for the whole family!', DATE_ADD(NOW(), INTERVAL 14 DAY) + INTERVAL 18 HOUR, 2, NOW() - INTERVAL 5 DAY);

-- Add indexes for better search performance
CREATE INDEX idx_posts_title ON posts(title);
CREATE INDEX idx_posts_content ON posts(content(255));
CREATE INDEX idx_posts_category_created ON posts(category_id, created_at);
CREATE INDEX idx_posts_likes ON posts(likes_count);
CREATE INDEX idx_posts_comments ON posts(comments_count);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_trending_topics_count ON trending_topics(post_count);
CREATE INDEX idx_events_date ON events(event_date);

-- Additional performance indexes
CREATE INDEX idx_posts_user_created ON posts(user_id, created_at);
CREATE INDEX idx_posts_pinned_created ON posts(is_pinned, created_at);
CREATE INDEX idx_posts_category_likes ON posts(category_id, likes_count);
CREATE INDEX idx_posts_category_comments ON posts(category_id, comments_count);
CREATE INDEX idx_comments_post_created ON comments(post_id, created_at);
CREATE INDEX idx_comments_user_created ON comments(user_id, created_at);
CREATE INDEX idx_post_likes_post_user ON post_likes(post_id, user_id);
CREATE INDEX idx_users_last_active ON users(last_active);
CREATE INDEX idx_events_date_created ON events(event_date, created_by);
CREATE INDEX idx_trending_topics_updated ON trending_topics(last_updated);

-- Composite indexes for common query patterns
CREATE INDEX idx_posts_category_pinned_created ON posts(category_id, is_pinned, created_at);
CREATE INDEX idx_posts_user_category_created ON posts(user_id, category_id, created_at);

-- Add full-text search indexes for better search functionality
ALTER TABLE posts ADD FULLTEXT(title, content);
ALTER TABLE users ADD FULLTEXT(username, bio);
