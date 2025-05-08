CREATE DATABASE IF NOT EXISTS note_taking_app;
USE note_taking_app;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    activation_token VARCHAR(255),
    reset_token VARCHAR(255), -- Added for password reset
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255), -- Added for image uploads
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme VARCHAR(50) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    font_size VARCHAR(10) DEFAULT '16px', -- Added for font size preference
    note_color VARCHAR(20) DEFAULT '#ffffff', -- Added for note color preference
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Thêm một người dùng với mật khẩu đã mã hóa bằng bcrypt
INSERT INTO users (username, email, password, is_active, role)
VALUES ('admin', 'admin@example.com', '$2y$10$q7YCkJHhty0S5OBVuZABQeID.7paB0MK0ez8wfPKNKDBfXPCa3nz2', 1, 'admin');