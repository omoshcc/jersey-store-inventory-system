<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }

// ---- CSV DOWNLOAD ----
if(isset($_GET['download'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="low_stock_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Jersey / Team', 'Size', 'Colour', 'Category', 'Current Stock', 'Reorder Point', 'Units Needed', 'Unit Price (KES)', 'Supplier', 'Supplier Phone']);
    $rows = $conn->query("
        SELECT i.team_name, i.size, i.colour, i.category,
               i.quantity, i.reorder_point,
               (i.reorder_point - i.quantity + 1) as units_needed,
               i.price,
               COALESCE(s.supplier_name,'N/A') as supplier_name,
               COALESCE(s.phone,'N/A') as phone
        FROM inventory i
        LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
        WHERE i.quantity <= i.reorder_point
        ORDER BY (i.reorder_point - i.quantity) DESC, i.category ASC
    ");
    while($r = $rows->fetch_assoc()) {
        fputcsv($out, [
            $r['team_name'], $r['size'], $r['colour'], $r['category'],
            $r['quantity'], $r['reorder_point'], $r['units_needed'],
            $r['price'], $r['supplier_name'], $r['phone']
        ]);
    }
    fclose($out);
    exit();
}

include("header.php");

// Items at or below reorder point
$low_items = $conn->query("
    SELECT i.*, s.supplier_name, s.phone, s.contact_person, s.email,
           (i.reorder_point - i.quantity + 1) as units_needed
    FROM inventory i
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    WHERE i.quantity <= i.reorder_point
    ORDER BY (i.reorder_point - i.quantity) DESC, i.category ASC
");

// Group by supplier for the action panel
$by_supplier = $conn->query("
    SELECT s.supplier_id, s.supplier_name, s.phone, s.contact_person,
           COUNT(i.inventory_id) as items_count,
           SUM(i.reorder_point - i.quantity + 1) as total_units_needed
    FROM inventory i
    JOIN suppliers s ON i.supplier_id = s.supplier_id
    WHERE i.quantity <= i.reorder_point
    GROUP BY s.supplier_id
    ORDER BY items_count DESC
");

// Totals
$total_low = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE quantity <= reorder_point")->fetch_assoc()['c'];
$total_ok  = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE quantity > reorder_point")->fetch_assoc()['c'];
$total_all = $total_low + $total_ok;

$cat_desc = ['A' => 'High Value (Originals)', 'B' => 'Medium (Tracksuits)', 'C' => 'Low Value (Accessories)'];
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Low Stock / Reorder Alert Report</h2>
        <div class="breadcrumb">Items that need restocking — grouped by supplier</div>
    </div>
    <div class="topbar-right" style="display:flex;gap:10px;align-items:center">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
        <a href="low_stock_report.php?download=1" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download CSV
        </a>
    </div>
</div>

<div class="page-content">

<!-- STATUS OVERVIEW -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card" style="border-left:4px solid #dc2626">
        <div class="stat-card-top">
            <div class="stat-label">Items Needing Restock</div>
            <div class="stat-icon icon-red">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
        </div>
        <div class="stat-value" style="color:#dc2626"><?php echo $total_low; ?></div>
        <div class="stat-sub">At or below reorder point</div>
    </div>
    <div class="stat-card green">
        <div class="stat-card-top">
            <div class="stat-label">Items Well Stocked</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_ok; ?></div>
        <div class="stat-sub">Above reorder point</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-top">
            <div class="stat-label">Suppliers to Contact</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $by_supplier->num_rows; ?></div>
        <div class="stat-sub">Suppliers with low-stock items</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Stock Health</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_all > 0 ? round(($total_ok / $total_all) * 100) : 100; ?>%</div>
        <div class="stat-sub">Items currently well stocked</div>
    </div>
</div>

<?php if($total_low == 0): ?>
<div class="card" style="text-align:center">
    <div class="card-body" style="padding:50px">
        <div style="font-size:40px;margin-bottom:12px">✅</div>
        <h3 style="color:var(--green-ok);margin-bottom:6px">All Stock Levels OK</h3>
        <p style="color:var(--muted)">No items are currently at or below their reorder point.</p>
    </div>
</div>
<?php else: ?>

<!-- BY SUPPLIER ACTION PANEL -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3>Suppliers to Contact for Restocking</h3>
        <span class="badge badge-red"><?php echo $by_supplier->num_rows; ?> suppliers</span>
    </div>
    <table>
        <thead><tr><th>Supplier</th><th>Contact Person</th><th>Phone</th><th>Items to Reorder</th><th>Est. Units Needed</th></tr></thead>
        <tbody>
        <?php $by_supplier->data_seek(0); while($r = $by_supplier->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                <td style="color:var(--gray-500)"><?php echo $r['contact_person'] ? htmlspecialchars($r['contact_person']) : '—'; ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:12px">
                    <?php if($r['phone']): ?>
                        <a href="tel:<?php echo htmlspecialchars($r['phone']); ?>" style="color:var(--green);font-weight:600"><?php echo htmlspecialchars($r['phone']); ?></a>
                    <?php else: echo '—'; endif; ?>
                </td>
                <td><span class="badge badge-red"><?php echo $r['items_count']; ?> items</span></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700"><?php echo number_format($r['total_units_needed']); ?> units</td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- LOW STOCK ITEMS TABLE -->
<div class="card">
    <div class="card-header">
        <h3>Low Stock Items (<?php echo $total_low; ?>)</h3>
        <a href="low_stock_report.php?download=1" class="btn btn-secondary btn-sm">⬇ Download CSV</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Jersey / Team</th><th>Size</th><th>Colour</th><th>Cat.</th>
                <th>Current Stock</th><th>Reorder At</th><th>Units Needed</th>
                <th>Unit Price (KES)</th><th>Supplier</th>
            </tr>
        </thead>
        <tbody>
        <?php while($r = $low_items->fetch_assoc()):
            $critical = $r['quantity'] == 0;
        ?>
            <tr style="background:<?php echo $critical ? '#fff0f0' : '#fffbf0'; ?>">
                <td style="font-weight:700"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><?php echo $r['size']; ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['colour']); ?></td>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td>
                    <?php if($critical): ?>
                        <span class="badge badge-red">0 — OUT OF STOCK</span>
                    <?php else: ?>
                        <span class="badge badge-red"><?php echo $r['quantity']; ?> ⚠</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--gray-500)"><?php echo $r['reorder_point']; ?></td>
                <td style="font-weight:700;color:#dc2626;font-family:'JetBrains Mono',monospace">+<?php echo max(1, $r['units_needed']); ?></td>
                <td style="font-family:'JetBrains Mono',monospace"><?php echo number_format($r['price'], 2); ?></td>
                <td style="font-size:12px">
                    <?php if($r['supplier_name']): ?>
                        <div style="font-weight:600"><?php echo htmlspecialchars($r['supplier_name']); ?></div>
                        <?php if($r['phone']): ?><div style="color:var(--gray-500)"><?php echo htmlspecialchars($r['phone']); ?></div><?php endif; ?>
                    <?php else: echo '<span style="color:var(--gray-400)">—</span>'; endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php endif; ?>
</div>
<?php include("footer.php"); ?>