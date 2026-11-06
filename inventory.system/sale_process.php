<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }

$user_id = (int)$_SESSION["user_id"];
$items   = $_POST['items'] ?? [];

if(empty($items)) { header("Location: sales.php?msg=error"); exit(); }

// Check if customer_name column exists
$cols = $conn->query("SHOW COLUMNS FROM transactions LIKE 'customer_name'");
$has_customer = $cols && $cols->num_rows > 0;
if(!$has_customer) {
    $conn->query("ALTER TABLE transactions ADD COLUMN customer_name VARCHAR(100) DEFAULT NULL");
}

$customer_name = trim($_POST['customer_name'] ?? '');

// Validate stock for ALL items first before recording anything
foreach($items as $item) {
    $inv_id = (int)$item['inventory_id'];
    $qty    = (int)$item['quantity'];
    $stock  = $conn->query("SELECT quantity FROM inventory WHERE inventory_id = $inv_id")->fetch_assoc();
    if(!$stock || $stock['quantity'] < $qty) {
        header("Location: sales.php?msg=nostock"); exit();
    }
}

// All checks passed — record each item
foreach($items as $item) {
    $inv_id = (int)$item['inventory_id'];
    $qty    = (int)$item['quantity'];
    $price  = (float)$item['sale_price'];
    $total  = $qty * $price;

    $stmt = $conn->prepare("INSERT INTO transactions (inventory_id, user_id, quantity_sold, sale_price, total_amount, customer_name) VALUES (?,?,?,?,?,?)");
    if(!$stmt) { header("Location: sales.php?msg=error"); exit(); }
    $stmt->bind_param("iiidds", $inv_id, $user_id, $qty, $price, $total, $customer_name);
    if(!$stmt->execute()) { header("Location: sales.php?msg=error"); exit(); }

    $conn->query("UPDATE inventory SET quantity = quantity - $qty WHERE inventory_id = $inv_id");

    // Low stock alert
    $row = $conn->query("SELECT quantity, reorder_point FROM inventory WHERE inventory_id = $inv_id")->fetch_assoc();
    if($row && $row['quantity'] <= $row['reorder_point']) {
        $exists = $conn->query("SELECT alert_id FROM low_stock_alerts WHERE inventory_id = $inv_id AND resolved = 0")->num_rows;
        if(!$exists) {
            $conn->query("INSERT INTO low_stock_alerts (inventory_id, triggered_at) VALUES ($inv_id, NOW())");
        }
    }
}

header("Location: sales.php?msg=recorded");
exit();
?>
