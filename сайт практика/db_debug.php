<?php
require_once 'includes/config.php';
$output = "";
$res = mysqli_query($conn, "DESCRIBE order_items");
while($row = mysqli_fetch_assoc($res)) {
    $output .= print_r($row, true);
}
file_put_contents('db_debug.txt', $output);
?>