CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(80) NOT NULL DEFAULT 'Geral',
    venue_name VARCHAR(200),
    address VARCHAR(255),
    state CHAR(2),
    city VARCHAR(120),
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    cover_image VARCHAR(255),
    status ENUM('draft', 'published', 'cancelled', 'finished') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_events_status (status),
    INDEX idx_events_starts_at (starts_at)
) ENGINE=InnoDB;

CREATE TABLE ticket_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity_total INT NOT NULL DEFAULT 0,
    quantity_sold INT NOT NULL DEFAULT 0,
    max_per_purchase INT NOT NULL DEFAULT 10,
    sale_start DATETIME NULL,
    sale_end DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('pending', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    mp_preference_id VARCHAR(120),
    mp_payment_id VARCHAR(120),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_orders_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    ticket_type_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id)
) ENGINE=InnoDB;

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    event_id INT NOT NULL,
    ticket_type_id INT NOT NULL,
    user_id INT NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    status ENUM('valid', 'used', 'cancelled') NOT NULL DEFAULT 'valid',
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_tickets_code (code)
) ENGINE=InnoDB;


-- ---------------------------------------------------------------
-- Usuário organizador inicial.
-- Login: admin@eventra.com   Senha: admin123
-- TROQUE ESSA SENHA assim que entrar no site pela primeira vez.
-- ---------------------------------------------------------------
INSERT INTO users (name, email, password_hash, role) VALUES
('Organizador Demo', 'admin@eventra.com', '$2y$10$e3xEhjhRhhPjz1Uh7yldh.FDv0w9uPQ90PNXbkDmKcm8buAA8Nddm', 'admin');
