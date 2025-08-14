CREATE DATABASE newsdb;

USE newsdb;

CREATE TABLE user (
user_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
user_name VARCHAR(100 ) NOT NULL UNIQUE,
pass VARCHAR(100) NOT NULL,
user_type VARCHAR(20)
);

CREATE TABLE articles (
article_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
title TEXT NOT NULL,
content TEXT NOT NULL,
type VARCHAR(5) NOT NULL,
img_name TEXT,
writer_id INT NOT NULL,
FOREIGN KEY (writer_id) REFERENCES user(user_id)
);

INSERT INTO user (user_name, pass, user_type) VALUES ('uoc', 'uoc', 'ADMIN');
