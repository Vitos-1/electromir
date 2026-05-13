<?php
require_once 'includes/config.php';
$res = mysqli_query($conn, "DESCRIBE order_items");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>