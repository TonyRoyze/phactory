CREATE DATABASE IF NOT EXISTS forum_db;

USE forum_db;

CREATE TABLE IF NOT EXISTS user (
    user_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    user_name VARCHAR(100) NOT NULL UNIQUE,
    pass VARCHAR(100) NOT NULL,
    user_type VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('BULLETIN', 'FORUM') NOT NULL,
    category VARCHAR(20) NOT NULL,
    img_name TEXT,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES user(user_id)
);

CREATE TABLE IF NOT EXISTS replies (
    reply_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES user(user_id)
);

-- Insert default admin user
INSERT INTO user (user_name, pass, user_type) VALUES ('uoc', 'uoc', 'ADMIN');

-- Insert sample member user for testing
INSERT INTO user (user_name, pass, user_type) VALUES ('testuser', 'password', 'MEMBER');

-- Insert sample bulletin posts
INSERT INTO posts (title, content, post_type, category, author_id) VALUES 
('Welcome to the Community Bulletin', 'This is our new community bulletin system where you can share announcements and participate in discussions.', 'BULLETIN', 'General', 1),
('Upcoming Community Event', 'Join us for our monthly community gathering this Saturday at 2 PM in the main hall.', 'BULLETIN', 'Events', 1);

-- Insert sample forum post
INSERT INTO posts (title, content, post_type, category, author_id) VALUES 
('General Discussion: Community Guidelines', 'Let us discuss what guidelines we should have for our community forum. Please share your thoughts!', 'FORUM', 'Discussions', 1);

-- Insert sample reply
INSERT INTO replies (post_id, content, author_id) VALUES 
(3, 'I think we should keep discussions respectful and on-topic. What do others think?', 2);