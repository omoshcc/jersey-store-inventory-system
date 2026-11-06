<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != 'Manager') {
    header("Location: dashboard.php"); exit();
}

$msg = $_GET['msg'] ?? '';

// Delete supplier - BEFORE any HTML output
if(isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM suppliers WHERE supplier_id = $del_id");
    header("Location: suppliers.php?msg=deleted"); exit();
}

// Add supplier - BEFORE any HTML output
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = trim($_POST['supplier_name']);
    $contact = trim($_POST['contact_person']);
    $phone   = trim($_POST['phone']);
    $email   = trim($_POST['email']);
    $address = trim($_POST['address']);
    $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $name, $contact, $phone, $email, $address);
    $stmt->execute();
    header("Location: suppliers.php?msg=added"); exit();
}

// HTML output starts here
include("header.php");
$suppliers = $conn->query("SELECT s.*, COUNT(i.inventory_id) as products FROM suppliers s LEFT JOIN inventory i ON s.supplier_id = i.supplier_id GROUP BY s.supplier_id ORDER BY s.supplier_name");
?>
<div class="topbar">
    <div><h2>Suppliers</h2><div class="sub">Manage supplier contacts and details</div></div>
</div>
<div class="page-content">
<?php if($msg == 'added'): ?><div class="alert alert-success">✓ Supplier added.</div><?php endif; ?>
<?php if($msg == 'deleted'): ?><div class="alert alert-danger">Supplier removed.</div><?php endif; ?>

<div class="two-col">
    <div class="card">
        <div class="card-header"><h3>Add New Supplier</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group" style="margin-bottom:14px">
                    <label>Supplier / Company Name *</label>
                    <input type="text" name="supplier_name" required placeholder="e.g. Nairobi Sports Wholesale">
                </div>
                <div class="form-grid" style="margin-bottom:14px">
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="07XX XXX XXX">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="supplier@email.com">
                </div>
                <div class="form-group" style="margin-bottom:16px">
                    <label>Address</label>
                    <textarea name="address" rows="2" placeholder="Physical address"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Add Supplier</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Supplier List</h3></div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Products</th><th>Action</th></tr></thead>
            <tbody>
            <?php if($suppliers->num_rows == 0): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">No suppliers added yet</td></tr>
            <?php else: while($r = $suppliers->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:600"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['contact_person'] ?: '—'); ?></td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px"><?php echo htmlspecialchars($r['phone'] ?: '—'); ?></td>
                    <td><span class="badge badge-green"><?php echo $r['products']; ?></span></td>
                    <td><a href="suppliers.php?delete=<?php echo $r['supplier_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this supplier?')">Delete</a></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>
<?php include("footer.php"); ?>
