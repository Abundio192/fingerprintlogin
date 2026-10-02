CREATE DATABASE IF NOT EXISTS fingerprint_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fingerprint_db;

CREATE TABLE IF NOT EXISTS students (

    id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(150) NOT NULL,

    fingerprint_id INT NOT NULL UNIQUE,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP

);