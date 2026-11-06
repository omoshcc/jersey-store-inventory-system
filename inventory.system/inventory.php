<?php
session_start();
include("connection.php");

$role = $_SESSION["role"];
$msg = $_GET['msg'] ?? '';

// Handle delete - BEFORE any HTML output
if(isset($_GET['delete']) && ($role == 'Manager' || $role == 'Storekeeper')) {
    $del_id = (int)$_GET['delete'];
    // Remove image file if exists
    $img = $conn->query("SELECT image_path FROM inventory WHERE inventory_id = $del_id")->fetch_assoc();
    if($img && $img['image_path'] && file_exists($img['image_path'])) {
        unlink($img['image_path']);
    }
    $conn->query("DELETE FROM inventory WHERE inventory_id = $del_id");
    header("Location: inventory.php?msg=deleted"); exit();
}

include("header.php");

// Search/filter
$search = $conn->real_escape_string($_GET['search'] ?? '');
$filter_cat = $conn->real_escape_string($_GET['category'] ?? '');
$where = "WHERE 1=1";
if($search) $where .= " AND (i.team_name LIKE '%$search%' OR i.colour LIKE '%$search%')";
if($filter_cat) $where .= " AND i.category = '$filter_cat'";

$inventory = $conn->query("SELECT i.*, s.supplier_name FROM inventory i LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id $where ORDER BY i.category ASC, i.team_name ASC");
?>

<div class="topbar">
    <div><h2>Inventory</h2><div class="sub">All jersey stock — sizes, colours, categories</div></div>
    <?php if($role == 'Manager' || $role == 'Storekeeper'): ?>
    <a href="stock_in.php" class="btn btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
        Add Stock / New Jersey
    </a>
    <?php endif; ?>
</div>

<div class="page-content">

<?php if($msg == 'added'): ?><div class="alert alert-success">✓ Jersey added successfully.</div><?php endif; ?>
<?php if($msg == 'deleted'): ?><div class="alert alert-danger">Jersey removed from inventory.</div><?php endif; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end">
            <div class="form-group" style="flex:1;margin:0">
                <label>Search</label>
                <input type="text" name="search" placeholder="Team name or colour..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group" style="width:160px;margin:0">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <option value="A" <?php echo $filter_cat=='A'?'selected':''; ?>>A — High Value</option>
                    <option value="B" <?php echo $filter_cat=='B'?'selected':''; ?>>B — Medium</option>
                    <option value="C" <?php echo $filter_cat=='C'?'selected':''; ?>>C — Low Value</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="inventory.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Stock List (<?php echo $inventory->num_rows; ?> items)</h3></div>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Photo</th><th>Team / Jersey</th><th>Size</th><th>Colour</th>
                <th>Category</th><th>In Stock</th><th>Reorder At</th>
                <th>Price (KES)</th><th>Supplier</th>
                <?php if($role == 'Manager' || $role == 'Storekeeper'): ?><th>Action</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if($inventory->num_rows == 0): ?>
            <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:30px">No inventory found. Add your first jersey.</td></tr>
        <?php else: $n=1; while($r = $inventory->fetch_assoc()):
            $low = $r['quantity'] <= $r['reorder_point'];
        ?>
            <tr <?php echo $low ? 'style="background:#fff5f5"' : ''; ?>>
                <td>
                    <?php if($r['image_path'] && file_exists($r['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($r['image_path']); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--border)">
                    <?php else: ?>
                        <div style="width:48px;height:48px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border)">
                            <svg width="18" height="18" fill="none" stroke="#9ca3af" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><?php echo $r['size']; ?></td>
                <td><?php echo htmlspecialchars($r['colour']); ?></td>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td>
                    <?php if($low): ?>
                        <span class="badge badge-red"><?php echo $r['quantity']; ?> ⚠</span>
                    <?php else: ?>
                        <span class="badge badge-green"><?php echo $r['quantity']; ?></span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--muted)"><?php echo $r['reorder_point']; ?></td>
                <td style="font-family:'DM Mono',monospace"><?php echo number_format($r['price'], 2); ?></td>
                <td style="color:var(--muted);font-size:12px"><?php echo $r['supplier_name'] ? htmlspecialchars($r['supplier_name']) : '—'; ?></td>
                <?php if($role == 'Manager' || $role == 'Storekeeper'): ?>
                <td>
                    <a href="inventory.php?delete=<?php echo $r['inventory_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this jersey from inventory?')">Delete</a>
                </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<?php include("footer.php"); ?>
