CREATE DATABASE prodmanage;

USE prodmanage;

CREATE TABLE part (
part_id INT NOT NULL AUTO_INCREMENT,
part_name VARCHAR(20) NOT NULL,
part_desc TEXT,
CONSTRAINT PK_part_id PRIMARY KEY (part_id)
);

CREATE TABLE workstation (
workstation_id INT NOT NULL AUTO_INCREMENT,
workstation_capacity INT NOT NULL,
is_active INT NOT NULL,
part_id INT NOT NULL,
CONSTRAINT PK_workstation_id PRIMARY KEY (workstation_id),
CONSTRAINT FK_part_id_workstation_table FOREIGN KEY (part_id) REFERENCES part (part_id)
);

CREATE TABLE user (
user_id INT NOT NULL AUTO_INCREMENT,
user_name VARCHAR(20) NOT NULL,
password VARCHAR(100) NOT NULL,
user_type VARCHAR(20) NOT NULL,
workstation_id INT,
CONSTRAINT PK_user_id PRIMARY KEY (user_id),
CONSTRAINT U_user_name UNIQUE (user_name),
CONSTRAINT FK_workstation_id_user_table FOREIGN KEY (workstation_id) REFERENCES workstation (workstation_id)
);

CREATE TABLE orders (
order_id INT NOT NULL AUTO_INCREMENT,
part_id INT NOT NULL,
quantity INT NOT NULL,
workstation_id INT NOT NULL,
order_status INT NOT NULL,
CONSTRAINT PK_order_id PRIMARY KEY (order_id),
CONSTRAINT FK_part_id_order_table FOREIGN KEY (part_id) REFERENCES part (part_id),
CONSTRAINT FK_workstation_id_order_table FOREIGN KEY (workstation_id) REFERENCES workstation (workstation_id)
);

INSERT INTO part (part_name, part_desc) VALUES
('Bolt', 'Standard metal bolt'),
('Nut', 'Hexagonal metal nut'),
('Washer', 'Flat metal washer'),
('Screw', 'Phillips head screw');

INSERT INTO workstation (workstation_capacity, is_active, part_id) VALUES
(100, 1, 1),
(150, 0, 2);

INSERT INTO user (user_name, password, user_type, workstation_id) VALUES
('uoc', 'uoc', 'ADMIN', NULL),
('jane_smith', 'jane', 'MANAGER', ),
('bob_johnson', 'bob', 'WORKSTATION', 1),
('alice_brown', 'alice', 'WORKSTATION', 2);

INSERT INTO orders (part_id, quantity, workstation_id, order_status) VALUES
(1, 50, 1, 0),
(2, 75, 2, 1);
