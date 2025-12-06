-- Script de création de la base de données et de la table Users
CREATE DATABASE IF NOT EXISTS tp_crud_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tp2_crud_php;

-- Suppression de la table si elle existe
DROP TABLE IF EXISTS users;

-- Création de la table users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'admin', 'author', 'editor') NOT NULL DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion de quelques données de test
INSERT INTO users (email, password, role) VALUES
('admin@example.com', '$2y$10$YourHashedPasswordHere1', 'admin'),
('author@example.com', '$2y$10$YourHashedPasswordHere2', 'author'),
('editor@example.com', '$2y$10$YourHashedPasswordHere3', 'editor'),
('guest@example.com', '$2y$10$YourHashedPasswordHere4', 'guest');
