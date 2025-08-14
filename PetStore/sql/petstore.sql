-- Create the database
CREATE DATABASE IF NOT EXISTS petstore;
USE petstore;

-- Customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20)
);

-- Pets table
CREATE TABLE pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    breed VARCHAR(100),
    age INT,
    price DECIMAL(10, 2) NOT NULL,
    customer_id INT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- Sales table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    pet_id INT,
    sale_date DATE,
    total_amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id)
);

-- Insert sample data
INSERT INTO customers (name, email, phone) VALUES
('John Doe', 'john.doe@example.com', '123-456-7890'),
('Jane Smith', 'jane.smith@example.com', '098-765-4321');

INSERT INTO pets (name, breed, age, price) VALUES
('Buddy', 'Golden Retriever', 2, 500.00),
('Lucy', 'Siamese Cat', 1, 300.00),
('Rocky', 'German Shepherd', 3, 600.00);

INSERT INTO sales (customer_id, pet_id, sale_date, total_amount) VALUES
(1, 1, '2025-07-10', 500.00),
(2, 2, '2025-07-12', 300.00);
