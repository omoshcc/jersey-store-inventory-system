<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }

// ---- CSV DOWNLOAD ----
if(isset($_GET['download'])) {
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to']   ?? '';
    $where = "WHERE 1=1";
    if($from) $where .= " AND DATE(t.transaction_date) >= '" . $conn->real_escape_string($from) . "'";
    if($to)   $where .= " AND DATE(t.transaction_date) <= '" . $conn->real_escape_string($to) . "'";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');

    fputcsv($out, ['Date', 'Jersey / Team', 'Size', 'Colour', 'Category', 'Qty Sold', 'Unit Price (KES)', 'Total (KES)', 'Processed By']);
    $rows = $conn->query("
        SELECT t.transaction_date, i.team_name, i.size, i.colour, i.category,
               t.quantity_sold, t.sale_price, t.total_amount, u.full_name
        FROM transactions t
        JOIN inventory i ON t.inventory_id = i.inventory_id
        JOIN users u ON t.user_id = u.user_id
        $where
        ORDER BY t.transaction_date DESC
    ");
    while($r = $rows->fetch_assoc()) {
        fputcsv($out, [
            date('d M Y H:i', strtotime($r['transaction_date'])),
            $r['team_name'], $r['size'], $r['colour'], $r['category'],
            $r['quantity_sold'], $r['sale_price'], $r['total_amount'], $r['full_name']
        ]);
    }
    fclose($out);
    exit();
}

include("header.php");

$from = $conn->real_escape_string($_GET['from'] ?? '');
$to   = $conn->real_escape_string($_GET['to']   ?? '');
$where = "WHERE 1=1";
if($from) $where .= " AND DATE(t.transaction_date) >= '$from'";
if($to)   $where .= " AND DATE(t.transaction_date) <= '$to'";

// Summary figures
$summary = $conn->query("
    SELECT COUNT(*) as total_txn,
           COALESCE(SUM(t.quantity_sold),0) as total_units,
           COALESCE(SUM(t.total_amount),0) as total_revenue
    FROM transactions t $where
")->fetch_assoc();

// Top 5 best-selling jerseys
$top_items = $conn->query("
    SELECT i.team_name, i.size, i.colour, i.category,
           SUM(t.quantity_sold) as units_sold,
           SUM(t.total_amount) as revenue
    FROM transactions t
    JOIN inventory i ON t.inventory_id = i.inventory_id
    $where
    GROUP BY t.inventory_id
    ORDER BY units_sold DESC LIMIT 5
");

// Sales by category
$by_cat = $conn->query("
    SELECT i.category,
           SUM(t.quantity_sold) as units,
           SUM(t.total_amount) as revenue
    FROM transactions t
    JOIN inventory i ON t.inventory_id = i.inventory_id
    $where
    GROUP BY i.category ORDER BY i.category
");

// All transactions (table)
$transactions = $conn->query("
    SELECT t.transaction_date, i.team_name, i.size, i.colour, i.category,
           t.quantity_sold, t.sale_price, t.total_amount, u.full_name
    FROM transactions t
    JOIN inventory i ON t.inventory_id = i.inventory_id
    JOIN users u ON t.user_id = u.user_id
    $where
    ORDER BY t.transaction_date DESC
");

$cat_desc = ['A' => 'High Value (Originals)', 'B' => 'Medium (Tracksuits)', 'C' => 'Low Value (Accessories)'];
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Sales Report</h2>
        <div class="breadcrumb">Full sales history with revenue breakdown</div>
    </div>
    <div class="topbar-right" style="display:flex;gap:10px;align-items:center">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
        <a href="sales_report.php?download=1&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download CSV
        </a>
    </div>
</div>

<div class="page-content">

<!-- DATE FILTER -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end">
            <div class="form-group" style="margin:0">
                <label>From Date</label>
                <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label>To Date</label>
                <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="sales_report.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
</div>

<!-- SUMMARY STATS -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card green">
        <div class="stat-card-top">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:20px">KES <?php echo number_format($summary['total_revenue'], 2); ?></div>
        <div class="stat-sub">From all filtered sales</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-top">
            <div class="stat-label">Total Transactions</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-3"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($summary['total_txn']); ?></div>
        <div class="stat-sub">Individual sales recorded</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Total Units Sold</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($summary['total_units']); ?></div>
        <div class="stat-sub">Jerseys sold</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-label">Avg. Sale Value</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px">KES <?php echo $summary['total_txn'] > 0 ? number_format($summary['total_revenue'] / $summary['total_txn'], 2) : '0.00'; ?></div>
        <div class="stat-sub">Per transaction</div>
    </div>
</div>

<div class="two-col" style="margin-bottom:24px">
    <!-- TOP SELLING -->
    <div class="card">
        <div class="card-header"><h3>Top 5 Best-Selling Jerseys</h3></div>
        <table>
            <thead><tr><th>#</th><th>Jersey</th><th>Cat.</th><th>Units Sold</th><th>Revenue (KES)</th></tr></thead>
            <tbody>
            <?php if($top_items->num_rows == 0): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">No sales data</td></tr>
            <?php else: $n=1; while($r = $top_items->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:800;color:var(--gray-400);font-size:16px"><?php echo $n++; ?></td>
                    <td>
                        <div style="font-weight:700"><?php echo htmlspecialchars($r['team_name']); ?></div>
                        <div style="font-size:11px;color:var(--gray-500)"><?php echo $r['size']; ?> / <?php echo htmlspecialchars($r['colour']); ?></div>
                    </td>
                    <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                    <td style="font-weight:700;font-family:'JetBrains Mono',monospace"><?php echo number_format($r['units_sold']); ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-ok)"><?php echo number_format($r['revenue'], 2); ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- SALES BY CATEGORY -->
    <div class="card">
        <div class="card-header"><h3>Revenue by Category</h3></div>
        <table>
            <thead><tr><th>Cat.</th><th>Description</th><th>Units Sold</th><th>Revenue (KES)</th></tr></thead>
            <tbody>
            <?php if($by_cat->num_rows == 0): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px">No data</td></tr>
            <?php else: while($r = $by_cat->fetch_assoc()): ?>
                <tr>
                    <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                    <td style="font-size:12px;color:var(--gray-500)"><?php echo $cat_desc[$r['category']] ?? ''; ?></td>
                    <td style="font-weight:700;font-family:'JetBrains Mono',monospace"><?php echo number_format($r['units']); ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-ok)"><?php echo number_format($r['revenue'], 2); ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ALL TRANSACTIONS -->
<div class="card">
    <div class="card-header">
        <h3>All Transactions (<?php echo number_format($transactions->num_rows); ?>)</h3>
        <a href="sales_report.php?download=1&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="btn btn-secondary btn-sm">⬇ Download CSV</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Date & Time</th><th>Jersey</th><th>Size</th><th>Colour</th>
                <th>Cat.</th><th>Qty</th><th>Unit Price (KES)</th><th>Total (KES)</th><th>Processed By</th>
            </tr>
        </thead>
        <tbody>
        <?php if($transactions->num_rows == 0): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px">No transactions found for this period.</td></tr>
        <?php else: while($r = $transactions->fetch_assoc()): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo date('d M Y H:i', strtotime($r['transaction_date'])); ?></td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><span class="badge badge-blue"><?php echo $r['size']; ?></span></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['colour']); ?></td>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td style="font-weight:700"><?php echo $r['quantity_sold']; ?></td>
                <td style="font-family:'JetBrains Mono',monospace"><?php echo number_format($r['sale_price'], 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-ok)"><?php echo number_format($r['total_amount'], 2); ?></td>
                <td style="color:var(--gray-500);font-size:12px"><?php echo htmlspecialchars($r['full_name']); ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
<?php include("footer.php"); ?>