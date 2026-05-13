<?php
require_once 'includes/config.php';

$queries = [
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_method ENUM('courier', 'pickup') DEFAULT 'courier'",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method ENUM('online', 'on_delivery') DEFAULT 'on_delivery'",
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS pickup_point_address TEXT",
    "ALTER TABLE orders MODIFY COLUMN customer_address TEXT"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " on query: $q\n";
    }
}
?>