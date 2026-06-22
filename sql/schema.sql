CREATE DATABASE IF NOT EXISTS books_api
 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE books_api;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS audit_log;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('member','admin') NOT NULL DEFAULT 'member',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE books (
 id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 author VARCHAR(150) NOT NULL,
 year SMALLINT NOT NULL,
 genre VARCHAR(80) NOT NULL DEFAULT 'Uncategorised',
 created_by INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_books_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_log (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actor_id INT NULL,
 action VARCHAR(50) NOT NULL,
 target VARCHAR(80) NULL,
 ip_address VARCHAR(45) NULL,
 detail VARCHAR(500) NULL,
 INDEX idx_action (action),
 INDEX idx_actor (actor_id)
) ENGINE=InnoDB;

-- password for both users: password
INSERT INTO users (id, name, email, password_hash, role) VALUES
 (1, 'Admin User', 'admin@books.test', '$2y$12$Uht6BCCC/DZky291h1MIu.YQLYnbo56Riem9.mIaZZlwdvk5XT.we', 'admin'),
 (2, 'Member User', 'member@books.test', '$2y$12$Uht6BCCC/DZky291h1MIu.YQLYnbo56Riem9.mIaZZlwdvk5XT.we', 'member');

INSERT INTO books (title, author, year, genre, created_by) VALUES
 ('Clean Code', 'Robert C. Martin', 2008, 'Software Engineering', 1),
 ('Eloquent JavaScript', 'Marijn Haverbeke', 2018, 'Programming', 2),
 ('Vue.js 3 By Example', 'John Au-Yeung', 2021, 'Web Development', 1);
