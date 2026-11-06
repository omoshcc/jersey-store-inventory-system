<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }
include("header.php");

$total_stock    = $conn->query("SELECT COALESCE(SUM(quantity),0) as t FROM inventory")->fetch_assoc()['t'];
$total_products = $conn->query("SELECT COUNT(*) as t FROM inventory")->fetch_assoc()['t'];
$today_sales    = $conn->query("SELECT COUNT(*) as t FROM transactions WHERE DATE(transaction_date)=CURDATE()")->fetch_assoc()['t'];
$today_revenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM transactions WHERE DATE(transaction_date)=CURDATE()")->fetch_assoc()['t'];
$low_stock_count= $conn->query("SELECT COUNT(*) as t FROM inventory WHERE quantity <= reorder_point")->fetch_assoc()['t'];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM transactions")->fetch_assoc()['t'];
$total_paid_out = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as t FROM supplier_payments")->fetch_assoc()['t'];
$net_profit     = $total_revenue - $total_paid_out;
$is_profit      = $net_profit >= 0;

// Chart — last 7 days
$chart_labels = []; $chart_data = [];
for($i=6;$i>=0;$i--) {
    $d = date('Y-m-d',strtotime("-$i days"));
    $chart_labels[] = date('D d',strtotime("-$i days"));
    $chart_data[]   = (float)$conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM transactions WHERE DATE(transaction_date)='$d'")->fetch_assoc()['t'];
}

$low_stock = $conn->query("SELECT team_name,size,colour,category,quantity,reorder_point FROM inventory WHERE quantity<=reorder_point ORDER BY quantity ASC LIMIT 5");
$recent    = $conn->query("SELECT t.transaction_date,i.team_name,i.size,t.quantity_sold,t.total_amount,u.full_name FROM transactions t JOIN inventory i ON t.inventory_id=i.inventory_id JOIN users u ON t.user_id=u.user_id ORDER BY t.transaction_date DESC LIMIT 7");
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Dashboard</h2>
        <div class="breadcrumb">Welcome back, <?php echo htmlspecialchars($full_name); ?> &mdash; <?php echo date('l, d F Y'); ?></div>
    </div>
    <div class="topbar-right">
        <?php if($role=='Manager'||$role=='Storekeeper'): ?>
        <a href="stock_in.php" class="btn btn-primary btn-sm">+ Add Stock</a>
        <?php endif; ?>
        <a href="sales.php" class="btn btn-dark btn-sm">+ Record Sale</a>
    </div>
</div>

<div class="page-content">

<?php if($low_stock_count > 0): ?>
<div class="alert alert-warning" style="margin-bottom:20px">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <strong><?php echo $low_stock_count; ?> item(s)</strong>&nbsp;are at or below their reorder point — check inventory now.
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr)">
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Total Units</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_stock); ?></div>
        <div class="stat-sub"><?php echo $total_products; ?> product lines</div>
    </div>

    <div class="stat-card blue">
        <div class="stat-card-top">
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px">KES <?php echo number_format($today_revenue,0); ?></div>
        <div class="stat-sub"><?php echo $today_sales; ?> sales today</div>
    </div>

    <div class="stat-card green">
        <div class="stat-card-top">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:16px">KES <?php echo number_format($total_revenue,0); ?></div>
        <div class="stat-sub">All time income</div>
    </div>

    <div class="stat-card <?php echo $is_profit?'green':'red'; ?>">
        <div class="stat-card-top">
            <div class="stat-label">Net Profit/Loss</div>
            <div class="stat-icon <?php echo $is_profit?'icon-green':'icon-red'; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:15px;color:<?php echo $is_profit?'var(--green-ok)':'var(--red)'; ?>"><?php echo $is_profit?'+':''; ?>KES <?php echo number_format($net_profit,0); ?></div>
        <div class="stat-sub"><a href="financials.php" style="color:inherit"><?php echo $is_profit?'▲ Profitable':'▼ At Loss'; ?></a></div>
    </div>

    <div class="stat-card red">
        <div class="stat-card-top">
            <div class="stat-label">Low Stock Alerts</div>
            <div class="stat-icon icon-red">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $low_stock_count; ?></div>
        <div class="stat-sub">Items need restocking</div>
    </div>
</div>

<!-- CHART + LOW STOCK -->
<div class="three-col">
    <div class="card">
        <div class="card-header">
            <h3>Sales Revenue — Last 7 Days</h3>
            <span class="badge badge-gold">Live</span>
        </div>
        <div class="card-body">
            <canvas id="salesChart" height="110"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>Low Stock Items</h3>
            <a href="inventory.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <table>
            <thead><tr><th>Jersey</th><th>Qty</th><th>Cat</th></tr></thead>
            <tbody>
            <?php if($low_stock->num_rows==0): ?>
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray-500)">✓ All stock levels OK</td></tr>
            <?php else: while($r=$low_stock->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?> <span style="color:var(--gray-500);font-weight:400;font-size:11px"><?php echo $r['size']; ?></span></td>
                    <td><span class="badge badge-red"><?php echo $r['quantity']; ?></span></td>
                    <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- RECENT TRANSACTIONS -->
<div class="card">
    <div class="card-header">
        <h3>Recent Transactions</h3>
        <a href="sales.php" class="btn btn-secondary btn-sm">View All Sales</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Date & Time</th><th>Jersey</th><th>Size</th><th>Qty Sold</th><th>Amount (KES)</th><th>Processed By</th></tr></thead>
        <tbody>
        <?php if($recent->num_rows==0): ?>
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-500)">No transactions yet</td></tr>
        <?php else: while($r=$recent->fetch_assoc()): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo date('d M Y H:i',strtotime($r['transaction_date'])); ?></td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><span class="badge badge-blue"><?php echo $r['size']; ?></span></td>
                <td><?php echo $r['quantity_sold']; ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700">KES <?php echo number_format($r['total_amount'],2); ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['full_name']); ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>

<script>
new Chart(document.getElementById('salesChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Revenue (KES)',
            data: <?php echo json_encode($chart_data); ?>,
            backgroundColor: 'rgba(240,180,41,0.15)',
            borderColor: '#f0b429',
            borderWidth: 2,
            borderRadius: 5,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f3f5' }, ticks: { font: { family: 'JetBrains Mono', size: 10 } } },
            x: { grid: { display: false }, ticks: { font: { family: 'Outfit', size: 12 } } }
        }
    }
});
</script>

<?php include("footer.php"); ?>
