CREATE DATABASE pastryplaza;

USE pastryplaza;

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL
);

INSERT INTO categories (category_name) VALUES ('Cake'), ('Pastry'), ('Cookies'), ('Cupcakes');

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT NOT NULL,
    image_name VARCHAR(255) NOT NULL,
    is_featured INT NOT NULL DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

INSERT INTO products(product_name, price, category_id, image_name, is_featured) VALUES 
('Apple Pie', 10.99, 2, 'applepie.jpg', 1),
('Chocolate Fudge Cake', 14.99, 1, 'chocolatefudgecake.jpg', 0),
('Red Velvet Cake', 13.99, 1, 'redvelvetcake.jpg', 1),
('Blueberry Cheese Cake', 20.99, 1, 'blueberrycheescake.jpg', 1),
('Cinnamon Roll', 4.99, 2, 'cinnamonroll.png', 1),
('Croissant', 4.99, 2, 'croissant.jpg', 0),
('Danish', 4.99, 2, 'danish.jpg', 0);


CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type INT NOT NULL DEFAULT 0
);

INSERT INTO users (username, password, user_type) VALUES 
('uoc', 'uoc', 1),
('tony', 'tony', 0)
;

-- CREATE TABLE cart (
--     cart_id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     product_id INT NOT NULL,
--     quantity INT NOT NULL,
--     FOREIGN KEY (user_id) REFERENCES users(user_id),
--     FOREIGN KEY (product_id) REFERENCES products(product_id)
-- );

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    address VARCHAR(255) NOT NULL,
    is_shipped INT NOT NULL DEFAULT 0,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

INSERT INTO orders (user_id, total_price,address) VALUES (1, 10.99, '123 Main St'), (2, 4.99, '456 Main St'), (2, 4.99, '789 Main St');

INSERT INTO orders (user_id, total_price, address, is_shipped) VALUES (1, 10.99, '123 Main St', 1), (2, 4.99, '456 Main St', 1);

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    order_id INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

INSERT INTO order_items (product_id, quantity, order_id) VALUES (1, 1, 1), (2, 1, 1), (3, 1, 2), (4, 1, 2), (5, 1, 3), (6, 1, 3), (7, 1, 3);

INSERT INTO order_items (product_id, quantity, order_id) VALUES (1, 1, 4), (2, 1, 4), (3, 1, 4), (4, 1, 5), (5, 1, 5), (6, 1, 5), (7, 1, 5);
