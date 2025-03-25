-- Script de creación de la base de datos para DentalMEC
-- Centro de Mecanizado Dental

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS camteks_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE camteks_db;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    company VARCHAR(100),
    role ENUM('client', 'admin', 'technician') NOT NULL DEFAULT 'client',
    remember_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expires DATETIME,
    active BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reference_number VARCHAR(15) NOT NULL UNIQUE,
    process_type ENUM('milling', 'sintering', 'printing') NOT NULL,
    status ENUM('new', 'pending', 'processing', 'completed', 'rejected') NOT NULL DEFAULT 'new',
    material VARCHAR(50),
    color VARCHAR(20),
    observations TEXT,
    estimated_completion_date DATE,
    completion_date DATETIME,
    total_price DECIMAL(10, 2),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de archivos
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de comentarios
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de historial de estados
CREATE TABLE IF NOT EXISTS status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('new', 'pending', 'processing', 'completed', 'rejected') NOT NULL,
    notes TEXT,
    created_by INT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de notificaciones
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    related_to ENUM('order', 'comment', 'system') NOT NULL,
    related_id INT,
    is_read BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de configuraciones de usuario
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    email_notifications BOOLEAN NOT NULL DEFAULT 1,
    sms_notifications BOOLEAN NOT NULL DEFAULT 0,
    language VARCHAR(5) NOT NULL DEFAULT 'es',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertar usuario administrador por defecto
INSERT INTO users (name, email, password, role)
VALUES (
    'Administrador',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- 'password'
    'admin'
) ON DUPLICATE KEY UPDATE id = id;

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_process_type ON orders(process_type);
CREATE INDEX idx_files_order_id ON files(order_id);
CREATE INDEX idx_comments_order_id ON comments(order_id);
CREATE INDEX idx_status_history_order_id ON status_history(order_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);

-- Mostrar un mensaje de confirmación
SELECT 'La estructura de la base de datos ha sido creada correctamente.' AS Message;
