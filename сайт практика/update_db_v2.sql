USE electronics_store;

-- Добавляем таблицу брендов
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Наполняем брендами
INSERT IGNORE INTO brands (name) VALUES 
('Apple'), ('Samsung'), ('Xiaomi'), ('Sony'), ('LG'), ('ASUS'), ('Lenovo'), ('Dell'), ('Logitech'), ('PlayStation'), ('Xbox');

-- Добавляем колонки в продукты
ALTER TABLE products ADD COLUMN brand_id INT AFTER category_id;
ALTER TABLE products ADD COLUMN ram VARCHAR(50) AFTER specifications;
ALTER TABLE products ADD COLUMN storage VARCHAR(50) AFTER ram;
ALTER TABLE products ADD FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL;

-- Обновляем существующие товары брендами
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Apple') WHERE name LIKE '%iPhone%' OR name LIKE '%MacBook%' OR name LIKE '%AirPods%' OR name LIKE '%Apple Watch%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Samsung') WHERE name LIKE '%Samsung%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') WHERE name LIKE '%Xiaomi%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Sony') WHERE name LIKE '%Sony%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'LG') WHERE name LIKE '%LG%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'ASUS') WHERE name LIKE '%ASUS%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Lenovo') WHERE name LIKE '%Lenovo%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Dell') WHERE name LIKE '%Dell%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Logitech') WHERE name LIKE '%Logitech%' OR name LIKE '%Keychron%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'PlayStation') WHERE name LIKE '%PlayStation%';
UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = 'Xbox') WHERE name LIKE '%Xbox%';

-- Добавляем примерные характеристики
UPDATE products SET ram = '8 ГБ', storage = '256 ГБ' WHERE name LIKE '%iPhone 15%';
UPDATE products SET ram = '12 ГБ', storage = '512 ГБ' WHERE name LIKE '%S24 Ultra%';
UPDATE products SET ram = '16 ГБ', storage = '512 ГБ' WHERE name LIKE '%MacBook%';
