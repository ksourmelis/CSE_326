CREATE DATABASE pothen_esxes_db;

USE pothen_esxes_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role ENUM('user', 'citizen', 'politician', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE declarations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    declaration_year YEAR NOT NULL,
    party VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    province VARCHAR(255) NOT NULL,
    properties TEXT,
    vehicles TEXT,
    shares TEXT,
    debts TEXT,
    income DECIMAL(15, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);