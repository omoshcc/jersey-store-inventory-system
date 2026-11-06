<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ['Manager','Storekeeper'])) {
    header("Location: dashboard.php"); exit();
}

$team_name     = trim($_POST['team_name']);
$colour        = trim($_POST['colour']);
$size          = $_POST['size'];
$category      = $_POST['category'];
$quantity      = (int)$_POST['quantity'];
$reorder_point = (int)$_POST['reorder_point'];
$price         = (float)$_POST['price'];
$supplier_id   = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

// Handle photo upload
$image_path = null;
if(!empty($_FILES['jersey_photo']['name'])) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $file_type = mime_content_type($_FILES['jersey_photo']['tmp_name']);
    $file_size = $_FILES['jersey_photo']['size'];

    if(in_array($file_type, $allowed) && $file_size <= 5 * 1024 * 1024) {
        if(!is_dir("uploads")) mkdir("uploads", 0755, true);
        $ext = pathinfo($_FILES['jersey_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'uploads/' . uniqid('jersey_') . '.' . strtolower($ext);
        if(move_uploaded_file($_FILES['jersey_photo']['tmp_name'], $filename)) {
            $image_path = $filename;
        }
    }
}

$stmt = $conn->prepare("INSERT INTO inventory (team_name, colour, size, category, quantity, reorder_point, price, supplier_id, image_path) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->bind_param("ssssiiids", $team_name, $colour, $size, $category, $quantity, $reorder_point, $price, $supplier_id, $image_path);

if($stmt->execute()) {
    if(!empty($_FILES['jersey_photo']['name']) && !$image_path) {
        header("Location: inventory.php?msg=imgfail");
    } else {
        header("Location: inventory.php?msg=added");
    }
} else {
    header("Location: add_inventory.php?msg=error");
}
exit();
?>
