<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }
include("header.php");

// Table counts
$tables = [
    'users'             => ['label' => 'Users',              'icon' => '👤'],
    'inventory'         => ['label' => 'Inventory Items',    'icon' => '📦'],
    'transactions'      => ['label' => 'Sales Transactions', 'icon' => '🧾'],
    'stock_deliveries'  => ['label' => 'Stock Deliveries',   'icon' => '🚚'],
    'suppliers'         => ['label' => 'Suppliers',          'icon' => '🏪'],
    'supplier_payments' => ['label' => 'Supplier Payments',  'icon' => '💳'],
    'low_stock_alerts'  => ['label' => 'Low Stock Alerts',   'icon' => '⚠️'],
    'user_feedback'     => ['label' => 'Feedback Entries',   'icon' => '⭐'],
];

$counts = [];
foreach($tables as $table => $info) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $table");
    $counts[$table] = $r ? (int)$r->fetch_assoc()['c'] : 0;
}

// Inventory breakdown
$inv_by_cat = $conn->query("SELECT category, COUNT(*) as items, SUM(quantity) as units FROM inventory GROUP BY category ORDER BY category");
$inv_by_supplier = $conn->query("SELECT s.supplier_name, COUNT(i.inventory_id) as items, SUM(i.quantity) as units FROM inventory i JOIN suppliers s ON i.supplier_id = s.supplier_id GROUP BY s.supplier_id ORDER BY items DESC");

// Sales summary
$sales_summary = $conn->query("SELECT COUNT(*) as total_txn, COALESCE(SUM(quantity_sold),0) as total_units, COALESCE(SUM(total_amount),0) as total_revenue FROM transactions");
$ss = $sales_summary->fetch_assoc();

// User roles
$user_roles = $conn->query("SELECT role, COUNT(*) as c FROM users GROUP BY role ORDER BY c DESC");

// Recent DB activity
$recent_txn = $conn->query("SELECT transaction_date FROM transactions ORDER BY transaction_date DESC LIMIT 1")->fetch_assoc();
$recent_del = $conn->query("SELECT delivery_date FROM stock_deliveries ORDER BY delivery_date DESC LIMIT 1")->fetch_assoc();
$recent_usr = $conn->query("SELECT created_at FROM users ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Database Report</h2>
        <div class="breadcrumb">Overview of all records stored in the system database</div>
    </div>
    <div class="topbar-right">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
    </div>
</div>

<div class="page-content">

<!-- TABLE RECORD COUNTS -->
<div style="margin-bottom:6px;font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.5px">Database Tables</div>
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
    <?php foreach($tables as $table => $info): ?>
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label"><?php echo $info['label']; ?></div>
            <div class="stat-icon icon-gold" style="font-size:16px"><?php echo $info['icon']; ?></div>
        </div>
        <div class="stat-value"><?php echo number_format($counts[$table]); ?></div>
        <div class="stat-sub"><?php echo $table; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- SUMMARY CARDS -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
    <div class="stat-card green">
        <div class="stat-card-top">
            <div class="stat-label">Total Sales Transactions</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($ss['total_txn']); ?></div>
        <div class="stat-sub"><?php echo number_format($ss['total_units']); ?> units sold — Ksh <?php echo number_format($ss['total_revenue'],2); ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-top">
            <div class="stat-label">Last Transaction</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:16px"><?php echo $recent_txn ? date('d M Y', strtotime($recent_txn['transaction_date'])) : 'None'; ?></div>
        <div class="stat-sub">Most recent sale recorded</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Last User Created</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:16px"><?php echo $recent_usr ? date('d M Y', strtotime($recent_usr['created_at'])) : 'None'; ?></div>
        <div class="stat-sub">Most recent account registered</div>
    </div>
</div>

<div class="two-col">
    <!-- INVENTORY BY CATEGORY -->
    <div class="card">
        <div class="card-header"><h3>Inventory by Category</h3></div>
        <table>
            <thead><tr><th>Category</th><th>Description</th><th>Product Lines</th><th>Total Units</th></tr></thead>
            <tbody>
            <?php
            $cat_desc = ['A'=>'High Value (Originals)','B'=>'Medium (Tracksuits)','C'=>'Low Value (Accessories)'];
            while($r = $inv_by_cat->fetch_assoc()): ?>
            <tr>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td><?php echo $cat_desc[$r['category']] ?? ''; ?></td>
                <td style="font-weight:700"><?php echo $r['items']; ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700"><?php echo number_format($r['units']); ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- USERS BY ROLE -->
    <div class="card">
        <div class="card-header"><h3>System Users by Role</h3></div>
        <table>
            <thead><tr><th>Role</th><th>Number of Users</th><th>Access Level</th></tr></thead>
            <tbody>
            <?php
            $access = ['Manager'=>'Full access','Storekeeper'=>'Inventory & stock','Sales Assistant'=>'Sales only'];
            while($r = $user_roles->fetch_assoc()): ?>
            <tr>
                <td><span class="badge badge-gold"><?php echo $r['role']; ?></span></td>
                <td style="font-weight:700;font-size:20px;font-family:'JetBrains Mono',monospace"><?php echo $r['c']; ?></td>
                <td style="color:var(--gray-500);font-size:12px"><?php echo $access[$r['role']] ?? ''; ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- INVENTORY BY SUPPLIER -->
<div class="card">
    <div class="card-header"><h3>Inventory by Supplier</h3></div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Supplier</th><th>Product Lines</th><th>Total Units in Stock</th></tr></thead>
        <tbody>
        <?php if($inv_by_supplier->num_rows == 0): ?>
            <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--gray-500)">No supplier data yet</td></tr>
        <?php else: while($r = $inv_by_supplier->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                <td><?php echo $r['items']; ?> lines</td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700"><?php echo number_format($r['units']); ?> units</td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
<?php include("footer.php"); ?>
