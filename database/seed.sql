PRAGMA foreign_keys = ON;

INSERT INTO products (sku, name, description, unit_price_cents, is_active) VALUES
('BOAK-TSHIRT', 'Blue Oak T-Shirt', 'Soft cotton tee with Blue Oak Software logo.', 2500, 1),
('BOAK-MUG',    'Blue Oak Mug',    'Ceramic mug. Dishwasher safe.',               1600, 1),
('BOAK-STICKER','Sticker Pack',    'A small pack of logo stickers.',               600, 1),
('BOAK-HAT',    'Blue Oak Hat',    'Adjustable cap with embroidered logo.',       2200, 1),
('BOAK-NOTE',   'Field Notes',     'Pocket notebook for project notes.',          1200, 1);

INSERT INTO users (email, display_name, role, password_hash, is_active) VALUES
('admin@example.com', 'Admin User', 'admin', '$2y$10$7CbSK2OpVq1EwQhz8a6CReSeseCKa35Y6nQbiIYFwzYaOA1/wkn8.', 1);
