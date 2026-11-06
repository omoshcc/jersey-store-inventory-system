<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }

// ---- CSV DOWNLOAD ----
if(isset($_GET['download'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Team / Jersey', 'Size', 'Colour', 'Category', 'In Stock', 'Reorder Point', 'Status', 'Unit Price (KES)', 'Stock Value (KES)', 'Supplier']);
    $rows = $conn->query("
        SELECT i.*, s.supplier_name,
               (i.quantity * i.price) as stock_value,
               IF(i.quantity <= i.reorder_point, 'LOW STOCK', 'OK') as status
        FROM inventory i
        LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
        ORDER BY i.category ASC, i.team_name ASC
    ");
    while($r = $rows->fetch_assoc()) {
        fputcsv($out, [
            $r['team_name'], $r['size'], $r['colour'], $r['category'],
            $r['quantity'], $r['reorder_point'], $r['status'],
            $r['price'], number_format($r['stock_value'], 2),
            $r['supplier_name'] ?? 'N/A'
        ]);
    }
    fclose($out);
    exit();
}

include("header.php");

$filter_cat = $conn->real_escape_string($_GET['category'] ?? '');
$filter_status = $_GET['status'] ?? '';

$where = "WHERE 1=1";
if($filter_cat) $where .= " AND i.category = '$filter_cat'";
if($filter_status === 'low') $where .= " AND i.quantity <= i.reorder_point";
if($filter_status === 'ok')  $where .= " AND i.quantity > i.reorder_point";

$inventory = $conn->query("
    SELECT i.*, s.supplier_name, (i.quantity * i.price) as stock_value
    FROM inventory i
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    $where
    ORDER BY i.category ASC, i.team_name ASC
");

// Totals
$totals = $conn->query("
    SELECT COUNT(*) as total_items,
           SUM(quantity) as total_units,
           SUM(quantity * price) as total_value,
           SUM(quantity <= reorder_point) as low_stock_count
    FROM inventory
")->fetch_assoc();

// By category
$by_cat = $conn->query("
    SELECT category,
           COUNT(*) as items,
           SUM(quantity) as units,
           SUM(quantity * price) as value
    FROM inventory GROUP BY category ORDER BY category
");

$cat_desc = ['A' => 'High Value (Originals)', 'B' => 'Medium (Tracksuits)', 'C' => 'Low Value (Accessories)'];
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Inventory Status Report</h2>
        <div class="breadcrumb">Full stock list with values and reorder status</div>
    </div>
    <div class="topbar-right" style="display:flex;gap:10px;align-items:center">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
        <a href="inventory_report.php?download=1" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download CSV
        </a>
    </div>
</div>

<div class="page-content">

<!-- SUMMARY -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card green">
        <div class="stat-card-top">
            <div class="stat-label">Total Stock Value</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px">KES <?php echo number_format($totals['total_value'], 2); ?></div>
        <div class="stat-sub">Value of all stock on hand</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-top">
            <div class="stat-label">Product Lines</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($totals['total_items']); ?></div>
        <div class="stat-sub"><?php echo number_format($totals['total_units']); ?> total units in stock</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #dc2626">
        <div class="stat-card-top">
            <div class="stat-label">Low Stock Items</div>
            <div class="stat-icon icon-red">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
        </div>
        <div class="stat-value" style="color:#dc2626"><?php echo number_format($totals['low_stock_count']); ?></div>
        <div class="stat-sub">Items at or below reorder point</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Avg. Unit Value</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px">KES <?php echo $totals['total_units'] > 0 ? number_format($totals['total_value'] / $totals['total_units'], 2) : '0.00'; ?></div>
        <div class="stat-sub">Per unit across all stock</div>
    </div>
</div>

<!-- CATEGORY BREAKDOWN -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>Stock Value by Category</h3></div>
    <table>
        <thead><tr><th>Category</th><th>Description</th><th>Product Lines</th><th>Total Units</th><th>Stock Value (KES)</th><th>% of Portfolio</th></tr></thead>
        <tbody>
        <?php while($r = $by_cat->fetch_assoc()):
            $pct = $totals['total_value'] > 0 ? round(($r['value'] / $totals['total_value']) * 100, 1) : 0;
        ?>
            <tr>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td style="color:var(--gray-500);font-size:12px"><?php echo $cat_desc[$r['category']] ?? ''; ?></td>
                <td style="font-weight:700"><?php echo $r['items']; ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700"><?php echo number_format($r['units']); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-ok)"><?php echo number_format($r['value'], 2); ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="background:#f3f4f6;border-radius:20px;height:6px;flex:1">
                            <div style="background:var(--green);width:<?php echo $pct; ?>%;height:6px;border-radius:20px"></div>
                        </div>
                        <span style="font-size:12px;font-weight:700;min-width:36px"><?php echo $pct; ?>%</span>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- FILTERS -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end">
            <div class="form-group" style="width:180px;margin:0">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <option value="A" <?php echo $filter_cat=='A'?'selected':''; ?>>A — High Value</option>
                    <option value="B" <?php echo $filter_cat=='B'?'selected':''; ?>>B — Medium</option>
                    <option value="C" <?php echo $filter_cat=='C'?'selected':''; ?>>C — Low Value</option>
                </select>
            </div>
            <div class="form-group" style="width:180px;margin:0">
                <label>Stock Status</label>
                <select name="status">
                    <option value="">All Items</option>
                    <option value="low" <?php echo $filter_status=='low'?'selected':''; ?>>⚠ Low Stock Only</option>
                    <option value="ok"  <?php echo $filter_status=='ok'?'selected':''; ?>>✓ OK Stock Only</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="inventory_report.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
</div>

<!-- FULL STOCK TABLE -->
<div class="card">
    <div class="card-header">
        <h3>Full Stock List (<?php echo $inventory->num_rows; ?> items)</h3>
        <a href="inventory_report.php?download=1" class="btn btn-secondary btn-sm">⬇ Download CSV</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Jersey / Team</th><th>Size</th><th>Colour</th><th>Cat.</th>
                <th>In Stock</th><th>Reorder At</th><th>Status</th>
                <th>Unit Price (KES)</th><th>Stock Value (KES)</th><th>Supplier</th>
            </tr>
        </thead>
        <tbody>
        <?php if($inventory->num_rows == 0): ?>
            <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:30px">No inventory found.</td></tr>
        <?php else: while($r = $inventory->fetch_assoc()):
            $low = $r['quantity'] <= $r['reorder_point'];
        ?>
            <tr <?php echo $low ? 'style="background:#fff5f5"' : ''; ?>>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><?php echo $r['size']; ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['colour']); ?></td>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td><?php if($low): ?>
                    <span class="badge badge-red"><?php echo $r['quantity']; ?> ⚠</span>
                <?php else: ?>
                    <span class="badge badge-green"><?php echo $r['quantity']; ?></span>
                <?php endif; ?></td>
                <td style="color:var(--gray-500)"><?php echo $r['reorder_point']; ?></td>
                <td><?php if($low): ?>
                    <span class="badge badge-red">Low Stock</span>
                <?php else: ?>
                    <span class="badge badge-green">OK</span>
                <?php endif; ?></td>
                <td style="font-family:'JetBrains Mono',monospace"><?php echo number_format($r['price'], 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700"><?php echo number_format($r['stock_value'], 2); ?></td>
                <td style="font-size:12px;color:var(--gray-500)"><?php echo $r['supplier_name'] ? htmlspecialchars($r['supplier_name']) : '—'; ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
<?php include("footer.php"); ?>