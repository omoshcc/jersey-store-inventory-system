<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != 'Manager') {
    header("Location: dashboard.php"); exit();
}

// ---- CSV DOWNLOAD ----
if(isset($_GET['download'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="supplier_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Delivery Date', 'Supplier', 'Jersey / Team', 'Size', 'Colour', 'Category', 'Qty Received', 'Unit Cost (KES)', 'Total Cost (KES)', 'Received By']);
    $rows = $conn->query("
        SELECT d.delivery_date, s.supplier_name, i.team_name, i.size, i.colour, i.category,
               d.quantity_received, d.unit_cost,
               (d.quantity_received * d.unit_cost) as total_cost,
               u.full_name
        FROM stock_deliveries d
        JOIN suppliers s ON d.supplier_id = s.supplier_id
        JOIN inventory i ON d.inventory_id = i.inventory_id
        JOIN users u ON d.received_by = u.user_id
        ORDER BY d.delivery_date DESC
    ");
    while($r = $rows->fetch_assoc()) {
        fputcsv($out, [
            date('d M Y', strtotime($r['delivery_date'])),
            $r['supplier_name'], $r['team_name'], $r['size'], $r['colour'], $r['category'],
            $r['quantity_received'], $r['unit_cost'],
            number_format($r['total_cost'], 2), $r['full_name']
        ]);
    }
    fclose($out);
    exit();
}

include("header.php");

$filter_supplier = (int)($_GET['supplier_id'] ?? 0);
$from = $conn->real_escape_string($_GET['from'] ?? '');
$to   = $conn->real_escape_string($_GET['to']   ?? '');

$where = "WHERE 1=1";
if($filter_supplier) $where .= " AND d.supplier_id = $filter_supplier";
if($from) $where .= " AND DATE(d.delivery_date) >= '$from'";
if($to)   $where .= " AND DATE(d.delivery_date) <= '$to'";

// Per-supplier summary
$supplier_summary = $conn->query("
    SELECT s.supplier_id, s.supplier_name, s.phone, s.contact_person,
           COUNT(d.delivery_id) as deliveries,
           SUM(d.quantity_received) as units_received,
           SUM(d.quantity_received * d.unit_cost) as total_cost,
           COALESCE((SELECT SUM(sp.amount_paid) FROM supplier_payments sp WHERE sp.supplier_id = s.supplier_id), 0) as total_paid,
           MAX(d.delivery_date) as last_delivery
    FROM suppliers s
    LEFT JOIN stock_deliveries d ON s.supplier_id = d.supplier_id
    GROUP BY s.supplier_id
    ORDER BY total_cost DESC
");

// All deliveries with filter
$deliveries = $conn->query("
    SELECT d.delivery_date, s.supplier_name, i.team_name, i.size, i.colour, i.category,
           d.quantity_received, d.unit_cost,
           (d.quantity_received * d.unit_cost) as total_cost,
           u.full_name
    FROM stock_deliveries d
    JOIN suppliers s ON d.supplier_id = s.supplier_id
    JOIN inventory i ON d.inventory_id = i.inventory_id
    JOIN users u ON d.received_by = u.user_id
    $where
    ORDER BY d.delivery_date DESC
");

// Totals
$totals = $conn->query("
    SELECT COUNT(*) as deliveries,
           COALESCE(SUM(quantity_received), 0) as units,
           COALESCE(SUM(quantity_received * unit_cost), 0) as cost
    FROM stock_deliveries d $where
")->fetch_assoc();

$suppliers_list = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
$cat_desc = ['A' => 'High Value', 'B' => 'Medium', 'C' => 'Low Value'];
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Supplier & Purchases Report</h2>
        <div class="breadcrumb">All stock deliveries and supplier payment status</div>
    </div>
    <div class="topbar-right" style="display:flex;gap:10px;align-items:center">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
        <a href="supplier_report.php?download=1" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download CSV
        </a>
    </div>
</div>

<div class="page-content">

<!-- SUMMARY -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-label">Total Deliveries</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($totals['deliveries']); ?></div>
        <div class="stat-sub"><?php echo number_format($totals['units']); ?> units received in total</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #dc2626">
        <div class="stat-card-top">
            <div class="stat-label">Total Procurement Cost</div>
            <div class="stat-icon icon-red">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px">KES <?php echo number_format($totals['cost'], 2); ?></div>
        <div class="stat-sub">Total paid/owed to suppliers</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-top">
            <div class="stat-label">Active Suppliers</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $supplier_summary->num_rows; ?></div>
        <div class="stat-sub">Suppliers with deliveries</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Avg. Delivery Cost</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px">KES <?php echo $totals['deliveries'] > 0 ? number_format($totals['cost'] / $totals['deliveries'], 2) : '0.00'; ?></div>
        <div class="stat-sub">Per delivery</div>
    </div>
</div>

<!-- SUPPLIER SUMMARY TABLE -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>Supplier Summary & Balance Status</h3></div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Supplier</th><th>Contact</th><th>Deliveries</th><th>Units Received</th><th>Total Cost (KES)</th><th>Total Paid (KES)</th><th>Balance Due (KES)</th><th>Last Delivery</th></tr></thead>
        <tbody>
        <?php $supplier_summary->data_seek(0); while($r = $supplier_summary->fetch_assoc()):
            $balance = $r['total_cost'] - $r['total_paid'];
            $balance_color = $balance > 0 ? '#dc2626' : '#1a7a3e';
        ?>
            <tr>
                <td>
                    <div style="font-weight:700"><?php echo htmlspecialchars($r['supplier_name']); ?></div>
                    <?php if($r['phone']): ?><div style="font-size:11px;color:var(--gray-500)"><?php echo htmlspecialchars($r['phone']); ?></div><?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--gray-500)"><?php echo $r['contact_person'] ? htmlspecialchars($r['contact_person']) : '—'; ?></td>
                <td style="font-weight:700;text-align:center"><?php echo $r['deliveries'] ?? 0; ?></td>
                <td style="font-family:'JetBrains Mono',monospace"><?php echo number_format($r['units_received'] ?? 0); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:#dc2626"><?php echo number_format($r['total_cost'] ?? 0, 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-ok)"><?php echo number_format($r['total_paid'] ?? 0, 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:800;color:<?php echo $balance_color; ?>">
                    <?php echo $balance > 0 ? number_format($balance, 2) : '<span class="badge badge-green">Fully Paid</span>'; ?>
                </td>
                <td style="font-size:12px;color:var(--gray-500)"><?php echo $r['last_delivery'] ? date('d M Y', strtotime($r['last_delivery'])) : '—'; ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="width:220px;margin:0">
                <label>Supplier</label>
                <select name="supplier_id">
                    <option value="">All Suppliers</option>
                    <?php while($s = $suppliers_list->fetch_assoc()): ?>
                    <option value="<?php echo $s['supplier_id']; ?>" <?php echo $filter_supplier == $s['supplier_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['supplier_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>From</label>
                <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label>To</label>
                <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="supplier_report.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
</div>

<!-- ALL DELIVERIES TABLE -->
<div class="card">
    <div class="card-header">
        <h3>Delivery History (<?php echo number_format($deliveries->num_rows); ?> records)</h3>
        <a href="supplier_report.php?download=1&supplier_id=<?php echo $filter_supplier; ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="btn btn-secondary btn-sm">⬇ Download CSV</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Date</th><th>Supplier</th><th>Jersey</th><th>Size</th>
                <th>Cat.</th><th>Qty Received</th><th>Unit Cost (KES)</th><th>Total Cost (KES)</th><th>Received By</th>
            </tr>
        </thead>
        <tbody>
        <?php if($deliveries->num_rows == 0): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px">No deliveries found for this selection.</td></tr>
        <?php else: while($r = $deliveries->fetch_assoc()): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo date('d M Y', strtotime($r['delivery_date'])); ?></td>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><?php echo $r['size']; ?></td>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td><span class="badge badge-green">+<?php echo $r['quantity_received']; ?></span></td>
                <td style="font-family:'JetBrains Mono',monospace"><?php echo number_format($r['unit_cost'], 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:#dc2626"><?php echo number_format($r['total_cost'], 2); ?></td>
                <td style="color:var(--gray-500);font-size:12px"><?php echo htmlspecialchars($r['full_name']); ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
<?php include("footer.php"); ?>