-- Banco de dados do Eventra
CREATE DATABASE IF NOT EXISTS ingressos_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ingressos_app;

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

-- Usuário organizador de exemplo (senha: admin123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Organizador Demo', 'admin@eventra.com', '$2y$10$e3xEhjhRhhPjz1Uh7yldh.FDv0w9uPQ90PNXbkDmKcm8buAA8Nddm', 'admin');

-- Evento de exemplo
INSERT INTO events (organizer_id, title, slug, description, category, venue_name, address, state, city, starts_at, ends_at, status) VALUES
(1, 'Festival de Música Eletrônica', 'festival-de-musica-eletronica', 'Um festival com os melhores DJs da cena eletrônica nacional e internacional.', 'Festival', 'Arena Central', 'Av. Principal, 1000', 'SP', 'São Paulo', DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY) + INTERVAL 8 HOUR, 'published');

INSERT INTO ticket_types (event_id, name, description, price, quantity_total, max_per_purchase) VALUES
(1, 'Pista', 'Acesso à pista comum', 120.00, 500, 6),
(1, 'VIP', 'Acesso à área VIP com open bar', 350.00, 100, 4);

-- Capa para o evento de exemplo original
UPDATE events SET cover_image = 'https://picsum.photos/seed/eventra-eletronica/900/560'
WHERE slug = 'festival-de-musica-eletronica';

-- Mais eventos de exemplo, para a listagem e os filtros ficarem povoados
INSERT INTO events (organizer_id, title, slug, description, category, venue_name, address, state, city, starts_at, ends_at, cover_image, status) VALUES
(1, 'Baile Charme na Laje', 'baile-charme-na-laje',
 'Clássicos do charme e do soul brasileiro numa laje com vista para a cidade. Discotecagem até o amanhecer.',
 'Festa / Balada', 'Laje do Zé', 'Rua da Ladeira, 42', 'RJ', 'Rio de Janeiro',
 DATE_ADD(NOW(), INTERVAL 12 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY) + INTERVAL 7 HOUR,
 'https://picsum.photos/seed/eventra-charme/900/560', 'published'),

(1, 'Piada Pronta — Stand-up', 'piada-pronta-stand-up',
 'Quatro comediantes testando material novo. Sem roteiro fechado, sem rede de proteção.',
 'Comédia / Stand-up', 'Teatro Subsolo', 'Av. Vergueiro, 900', 'SP', 'São Paulo',
 DATE_ADD(NOW(), INTERVAL 6 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY) + INTERVAL 2 HOUR,
 'https://picsum.photos/seed/eventra-standup/900/560', 'published'),

(1, 'Feira de Vinil e Zines', 'feira-de-vinil-e-zines',
 'Mais de 40 expositores com discos usados, fanzines, cartazes e fitas. Entrada com direito a sacola.',
 'Feira / Mercado', 'Galpão 7', 'Rua São Francisco, 210', 'PR', 'Curitiba',
 DATE_ADD(NOW(), INTERVAL 20 DAY), DATE_ADD(NOW(), INTERVAL 20 DAY) + INTERVAL 9 HOUR,
 'https://picsum.photos/seed/eventra-vinil/900/560', 'published'),

(1, 'Porão: Punk Rock Night', 'porao-punk-rock-night',
 'Cinco bandas da cena local em três horas de show. Abertura dos portões às 20h.',
 'Show / Música', 'Porão 13', 'Rua Voluntários, 77', 'RS', 'Porto Alegre',
 DATE_ADD(NOW(), INTERVAL 45 DAY), DATE_ADD(NOW(), INTERVAL 45 DAY) + INTERVAL 5 HOUR,
 'https://picsum.photos/seed/eventra-punk/900/560', 'published'),

(1, 'Oficina de Discotecagem', 'oficina-de-discotecagem',
 'Aula prática de mixagem para iniciantes, com equipamento disponível no local. Vagas limitadas.',
 'Workshop / Curso', 'Estúdio Norte', 'Rua dos Aimorés, 1500', 'MG', 'Belo Horizonte',
 DATE_ADD(NOW(), INTERVAL 9 DAY), DATE_ADD(NOW(), INTERVAL 9 DAY) + INTERVAL 4 HOUR,
 'https://picsum.photos/seed/eventra-oficina/900/560', 'published');

INSERT INTO ticket_types (event_id, name, description, price, quantity_total, max_per_purchase) VALUES
((SELECT id FROM (SELECT id FROM events WHERE slug='baile-charme-na-laje') t), 'Pista', 'Entrada individual', 45.00, 250, 6),
((SELECT id FROM (SELECT id FROM events WHERE slug='baile-charme-na-laje') t), 'Lote Amigo (4un)', 'Pacote com quatro entradas', 160.00, 40, 2),
((SELECT id FROM (SELECT id FROM events WHERE slug='piada-pronta-stand-up') t), 'Plateia', 'Assento numerado', 60.00, 120, 4),
((SELECT id FROM (SELECT id FROM events WHERE slug='piada-pronta-stand-up') t), 'Mezanino', 'Vista lateral', 40.00, 60, 4),
((SELECT id FROM (SELECT id FROM events WHERE slug='feira-de-vinil-e-zines') t), 'Entrada', 'Acesso o dia todo', 20.00, 600, 8),
((SELECT id FROM (SELECT id FROM events WHERE slug='porao-punk-rock-night') t), 'Antecipado', 'Primeiro lote', 35.00, 200, 6),
((SELECT id FROM (SELECT id FROM events WHERE slug='porao-punk-rock-night') t), 'Na porta', 'Sujeito à lotação', 50.00, 80, 2),
((SELECT id FROM (SELECT id FROM events WHERE slug='oficina-de-discotecagem') t), 'Vaga', 'Inclui uso do equipamento', 180.00, 18, 2);
