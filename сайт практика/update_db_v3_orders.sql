USE electronics_store;

-- Добавляем колонки в таблицу заказов
ALTER TABLE orders 
    MODIFY COLUMN customer_address TEXT NULL,
    ADD COLUMN delivery_method ENUM('courier', 'pickup') DEFAULT 'courier' AFTER customer_email,
    ADD COLUMN payment_method ENUM('online', 'on_delivery') DEFAULT 'on_delivery' AFTER delivery_method,
    ADD COLUMN pickup_point_address TEXT AFTER payment_method;
