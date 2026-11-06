<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ['Manager','Storekeeper'])) {
    header("Location: dashboard.php"); exit();
}

$mode        = $_POST['mode'] ?? 'existing';
$supplier_id = (int)$_POST['supplier_id'];
$qty         = (int)$_POST['quantity_received'];
$unit_cost   = (float)$_POST['unit_cost'];
$date        = $conn->real_escape_string($_POST['delivery_date']);
$user_id     = (int)$_SESSION["user_id"];

// ---- EXISTING JERSEY ----
if($mode === 'existing') {
    $inventory_id = (int)$_POST['inventory_id'];

    $stmt = $conn->prepare("INSERT INTO stock_deliveries (supplier_id, inventory_id, quantity_received, delivery_date, received_by, unit_cost) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iiisid", $supplier_id, $inventory_id, $qty, $date, $user_id, $unit_cost);

    if($stmt->execute()) {
        $conn->query("UPDATE inventory SET quantity = quantity + $qty WHERE inventory_id = $inventory_id");
        $conn->query("UPDATE low_stock_alerts SET resolved=1, resolved_at=NOW() WHERE inventory_id=$inventory_id AND resolved=0");
        header("Location: stock_in.php?msg=added");
    } else {
        header("Location: stock_in.php?msg=error");
    }
    exit();
}

// ---- NEW JERSEY ----
if($mode === 'new') {
    $team_name     = trim($_POST['team_name']);
    $colour        = trim($_POST['colour']);
    $size          = $_POST['size'];
    $category      = $_POST['category'];
    $price         = (float)$_POST['price'];
    $reorder_point = (int)$_POST['reorder_point'];

    // Handle photo upload
    $image_path = null;
    if(!empty($_FILES['jersey_photo']['name'])) {
        $allowed   = ['image/jpeg','image/png','image/webp'];
        $file_type = mime_content_type($_FILES['jersey_photo']['tmp_name']);
        if(in_array($file_type, $allowed) && $_FILES['jersey_photo']['size'] <= 5*1024*1024) {
            if(!is_dir("uploads")) mkdir("uploads", 0755, true);
            $ext      = pathinfo($_FILES['jersey_photo']['name'], PATHINFO_EXTENSION);
            $filename = 'uploads/' . uniqid('jersey_') . '.' . strtolower($ext);
            if(move_uploaded_file($_FILES['jersey_photo']['tmp_name'], $filename)) {
                $image_path = $filename;
            }
        }
    }

    // Step 1: Insert jersey into inventory
    $stmt = $conn->prepare("INSERT INTO inventory (team_name, colour, size, category, quantity, reorder_point, price, supplier_id, image_path) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssiidis", $team_name, $colour, $size, $category, $qty, $reorder_point, $price, $supplier_id, $image_path);

    if(!$stmt->execute()) {
        header("Location: stock_in.php?msg=error"); exit();
    }
    $new_inventory_id = $conn->insert_id;

    // Step 2: Record the delivery
    $stmt2 = $conn->prepare("INSERT INTO stock_deliveries (supplier_id, inventory_id, quantity_received, delivery_date, received_by, unit_cost) VALUES (?,?,?,?,?,?)");
    $stmt2->bind_param("iiisid", $supplier_id, $new_inventory_id, $qty, $date, $user_id, $unit_cost);

    if($stmt2->execute()) {
        header("Location: stock_in.php?msg=added_new");
    } else {
        header("Location: stock_in.php?msg=error");
    }
    exit();
}
?>
