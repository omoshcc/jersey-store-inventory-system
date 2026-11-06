<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != 'Manager') {
    header("Location: dashboard.php"); exit();
}

// ---- CSV DOWNLOAD ----
if(isset($_GET['download'])) {
    $type = $_GET['type'] ?? 'sales';
    if($type === 'deliveries') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_deliveries_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Received By', 'Role', 'Jersey', 'Size', 'Supplier', 'Qty Received', 'Unit Cost (KES)', 'Total Cost (KES)']);
        $rows = $conn->query("
            SELECT d.delivery_date, u.full_name, u.role, i.team_name, i.size, s.supplier_name,
                   d.quantity_received, d.unit_cost, (d.quantity_received * d.unit_cost) as total_cost
            FROM stock_deliveries d
            JOIN users u ON d.received_by = u.user_id
            JOIN inventory i ON d.inventory_id = i.inventory_id
            JOIN suppliers s ON d.supplier_id = s.supplier_id
            ORDER BY d.delivery_date DESC
        ");
        while($r = $rows->fetch_assoc()) {
            fputcsv($out, [
                date('d M Y', strtotime($r['delivery_date'])),
                $r['full_name'], $r['role'], $r['team_name'], $r['size'],
                $r['supplier_name'], $r['quantity_received'], $r['unit_cost'],
                number_format($r['total_cost'], 2)
            ]);
        }
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_sales_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Processed By', 'Role', 'Jersey', 'Size', 'Colour', 'Category', 'Qty Sold', 'Unit Price (KES)', 'Total (KES)']);
        $rows = $conn->query("
            SELECT t.transaction_date, u.full_name, u.role, i.team_name, i.size, i.colour, i.category,
                   t.quantity_sold, t.sale_price, t.total_amount
            FROM transactions t
            JOIN users u ON t.user_id = u.user_id
            JOIN inventory i ON t.inventory_id = i.inventory_id
            ORDER BY t.transaction_date DESC
        ");
        while($r = $rows->fetch_assoc()) {
            fputcsv($out, [
                date('d M Y H:i', strtotime($r['transaction_date'])),
                $r['full_name'], $r['role'], $r['team_name'], $r['size'],
                $r['colour'], $r['category'],
                $r['quantity_sold'], $r['sale_price'], $r['total_amount']
            ]);
        }
    }
    fclose($out);
    exit();
}

include("header.php");

$filter_user = (int)($_GET['user_id'] ?? 0);
$from = $conn->real_escape_string($_GET['from'] ?? '');
$to   = $conn->real_escape_string($_GET['to']   ?? '');

$sale_where = "WHERE 1=1";
$del_where  = "WHERE 1=1";
if($filter_user) { $sale_where .= " AND t.user_id = $filter_user"; $del_where .= " AND d.received_by = $filter_user"; }
if($from) { $sale_where .= " AND DATE(t.transaction_date) >= '$from'"; $del_where .= " AND DATE(d.delivery_date) >= '$from'"; }
if($to)   { $sale_where .= " AND DATE(t.transaction_date) <= '$to'";   $del_where .= " AND DATE(d.delivery_date) <= '$to'"; }

// Per-user activity summary
$user_activity = $conn->query("
    SELECT u.user_id, u.full_name, u.role,
           (SELECT COUNT(*) FROM transactions WHERE user_id = u.user_id) as sales_count,
           (SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE user_id = u.user_id) as sales_value,
           (SELECT COUNT(*) FROM stock_deliveries WHERE received_by = u.user_id) as deliveries_count,
           (SELECT COALESCE(SUM(quantity_received * unit_cost),0) FROM stock_deliveries WHERE received_by = u.user_id) as deliveries_value
    FROM users u
    ORDER BY sales_count DESC, u.full_name ASC
");

// Sales log
$sales_log = $conn->query("
    SELECT t.transaction_date, u.full_name, u.role, i.team_name, i.size, i.colour, i.category,
           t.quantity_sold, t.sale_price, t.total_amount
    FROM transactions t
    JOIN users u ON t.user_id = u.user_id
    JOIN inventory i ON t.inventory_id = i.inventory_id
    $sale_where
    ORDER BY t.transaction_date DESC LIMIT 100
");

// Deliveries log
$deliveries_log = $conn->query("
    SELECT d.delivery_date, u.full_name, u.role, i.team_name, i.size,
           s.supplier_name, d.quantity_received, d.unit_cost,
           (d.quantity_received * d.unit_cost) as total_cost
    FROM stock_deliveries d
    JOIN users u ON d.received_by = u.user_id
    JOIN inventory i ON d.inventory_id = i.inventory_id
    JOIN suppliers s ON d.supplier_id = s.supplier_id
    $del_where
    ORDER BY d.delivery_date DESC LIMIT 100
");

$users_list = $conn->query("SELECT user_id, full_name, role FROM users ORDER BY full_name");
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>User Activity & Audit Report</h2>
        <div class="breadcrumb">Track who recorded what — sales and stock-in activity log</div>
    </div>
    <div class="topbar-right" style="display:flex;gap:10px;align-items:center">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
        <a href="audit_report.php?download=1&type=sales" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Sales CSV
        </a>
        <a href="audit_report.php?download=1&type=deliveries" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Deliveries CSV
        </a>
    </div>
</div>

<div class="page-content">

<!-- USER ACTIVITY SUMMARY -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>User Activity Summary</h3></div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>User</th><th>Role</th>
                <th>Sales Recorded</th><th>Sales Value (KES)</th>
                <th>Deliveries Received</th><th>Deliveries Value (KES)</th>
            </tr>
        </thead>
        <tbody>
        <?php while($r = $user_activity->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <td><span class="badge badge-gold" style="font-size:10px"><?php echo $r['role']; ?></span></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700"><?php echo number_format($r['sales_count']); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;color:var(--green-ok);font-weight:700"><?php echo number_format($r['sales_value'], 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700"><?php echo number_format($r['deliveries_count']); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;color:#dc2626;font-weight:700"><?php echo number_format($r['deliveries_value'], 2); ?></td>
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
                <label>Filter by User</label>
                <select name="user_id">
                    <option value="">All Users</option>
                    <?php while($u = $users_list->fetch_assoc()): ?>
                    <option value="<?php echo $u['user_id']; ?>" <?php echo $filter_user == $u['user_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['role']; ?>)
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
            <a href="audit_report.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
</div>

<!-- SALES LOG -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h3>Sales Activity Log (<?php echo $sales_log->num_rows; ?> records — max 100)</h3>
        <a href="audit_report.php?download=1&type=sales&user_id=<?php echo $filter_user; ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="btn btn-secondary btn-sm">⬇ Sales CSV</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Date & Time</th><th>Processed By</th><th>Role</th>
                <th>Jersey</th><th>Size</th><th>Colour</th><th>Cat.</th>
                <th>Qty</th><th>Total (KES)</th>
            </tr>
        </thead>
        <tbody>
        <?php if($sales_log->num_rows == 0): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:20px">No sales found for this filter.</td></tr>
        <?php else: while($r = $sales_log->fetch_assoc()): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo date('d M Y H:i', strtotime($r['transaction_date'])); ?></td>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <td><span class="badge badge-gold" style="font-size:10px"><?php echo $r['role']; ?></span></td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><?php echo $r['size']; ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['colour']); ?></td>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td style="font-weight:700"><?php echo $r['quantity_sold']; ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-ok)"><?php echo number_format($r['total_amount'], 2); ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- DELIVERIES LOG -->
<div class="card">
    <div class="card-header">
        <h3>Stock-In Activity Log (<?php echo $deliveries_log->num_rows; ?> records — max 100)</h3>
        <a href="audit_report.php?download=1&type=deliveries&user_id=<?php echo $filter_user; ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="btn btn-secondary btn-sm">⬇ Deliveries CSV</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Date</th><th>Received By</th><th>Role</th>
                <th>Jersey</th><th>Size</th><th>Supplier</th>
                <th>Qty</th><th>Unit Cost (KES)</th><th>Total Cost (KES)</th>
            </tr>
        </thead>
        <tbody>
        <?php if($deliveries_log->num_rows == 0): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:20px">No deliveries found for this filter.</td></tr>
        <?php else: while($r = $deliveries_log->fetch_assoc()): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo date('d M Y', strtotime($r['delivery_date'])); ?></td>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <td><span class="badge badge-gold" style="font-size:10px"><?php echo $r['role']; ?></span></td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><?php echo $r['size']; ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                <td><span class="badge badge-green">+<?php echo $r['quantity_received']; ?></span></td>
                <td style="font-family:'JetBrains Mono',monospace"><?php echo number_format($r['unit_cost'], 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:#dc2626"><?php echo number_format($r['total_cost'], 2); ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
<?php include("footer.php"); ?>