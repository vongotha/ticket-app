-- 1. Création de la base de données
CREATE DATABASE IF NOT EXISTS `ticket_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ticket_db`;

-- 2. Désactiver les contraintes temporairement pour le déploiement propre
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- TABLE : USERS
-- ========================================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('employe', 'technicien', 'admin') DEFAULT 'employe',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================================
-- TABLE : CATEGORIES (Utile pour l'entraînement et le routage de l'IA)
-- ========================================================
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT NULL
) ENGINE=InnoDB;

-- ========================================================
-- TABLE : TICKETS
-- ========================================================
CREATE TABLE `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titre` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `statut` ENUM('ouvert', 'en_cours', 'resolu') DEFAULT 'ouvert',
    `priorite` ENUM('basse', 'moyenne', 'haute') DEFAULT 'moyenne',
    
    -- Relations
    `client_id` INT NOT NULL,              -- L'employé qui a créé le ticket
    `categorie_id` INT NULL,              -- Prédit automatiquement par l'IA
    `technicien_id` INT NULL,             -- Assigné automatiquement selon la catégorie
    
    -- Tracabilité RTM
    `provenance` ENUM('web', 'email') DEFAULT 'web', 
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`client_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`categorie_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`technicien_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================================
-- TABLE : NOTIFICATIONS (Pour le système Push de la partie RTM)
-- ========================================================
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,               -- Le technicien ou l'employé à notifier
    `ticket_id` INT NOT NULL,
    `message` VARCHAR(255) NOT NULL,
    `est_lu` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;