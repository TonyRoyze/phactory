CREATE DATABASE IF NOT EXISTS helpdesk_db;

USE helpdesk_db;

-- Modified users table with additional fields for helpdesk system
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    user_role ENUM('CUSTOMER', 'ADMIN') DEFAULT 'CUSTOMER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table for ticket classification
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color_code VARCHAR(7) DEFAULT '#2E6DA4',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Main tickets table
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('Technical', 'Billing', 'General') NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
    customer_id INT NOT NULL,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id)
);

-- Ticket replies table for communication
CREATE TABLE IF NOT EXISTS ticket_replies (
    reply_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id)
);

-- File attachments table
CREATE TABLE IF NOT EXISTS attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NULL,
    reply_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (reply_id) REFERENCES ticket_replies(reply_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- Insert default categories
INSERT INTO categories (name, description, color_code) VALUES 
('Technical', 'Technical support and troubleshooting issues', '#d9534f'),
('Billing', 'Billing inquiries and payment-related issues', '#f0ad4e'),
('General', 'General inquiries and other support requests', '#5cb85c');

-- Insert default admin user
INSERT INTO users (username, email, password, full_name, user_role) VALUES 
('admin', 'admin@helpdesk.com', 'admin123', 'System Administrator', 'ADMIN');

-- Insert sample customer user for testing
INSERT INTO users (username, email, password, full_name, user_role) VALUES 
('customer1', 'customer1@example.com', 'password123', 'John Customer', 'CUSTOMER'),
('customer2', 'customer2@example.com', 'password123', 'Jane Smith', 'CUSTOMER');

-- Insert sample support agent
INSERT INTO users (username, email, password, full_name, user_role) VALUES 
('support1', 'support1@helpdesk.com', 'support123', 'Support Agent One', 'ADMIN');

-- Insert sample tickets for testing
INSERT INTO tickets (title, description, category, priority, customer_id) VALUES 
('Cannot login to my account', 'I am unable to login to my account. I keep getting an error message saying invalid credentials even though I am sure my password is correct.', 'Technical', 'High', 2),
('Question about billing cycle', 'I would like to understand when my billing cycle starts and ends. Can someone please clarify this for me?', 'Billing', 'Medium', 3),
('How to update my profile information', 'I need to update my contact information in my profile but cannot find where to do this. Please help.', 'General', 'Low', 2);

-- Insert sample replies
INSERT INTO ticket_replies (ticket_id, author_id, content, is_internal) VALUES 
(1, 4, 'I have reviewed your account and can see the issue. Let me reset your password for you.', FALSE),
(1, 4, 'Customer seems to have caps lock on when typing password', TRUE),
(2, 4, 'Your billing cycle runs from the 1st to the last day of each month. You will receive your invoice on the 25th of each month.', FALSE),
(3, 2, 'Thank you for the quick response! I found the profile section now.', FALSE);