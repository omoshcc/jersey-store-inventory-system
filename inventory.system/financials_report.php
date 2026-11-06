<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != 'Manager') {
    header("Location: dashboard.php"); exit();
}

// ---- CSV DOWNLOAD ----
if(isset($_GET['download'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="financials_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Jersey / Team', 'Size', 'Colour', 'Category', 'Units Sold', 'Total Revenue (KES)', 'Total Cost (KES)', 'Gross Profit (KES)', 'Margin %']);
    $rows = $conn->query("
        SELECT i.team_name, i.size, i.colour, i.category,
               COALESCE(SUM(t.quantity_sold), 0) as units_sold,
               COALESCE(SUM(t.total_amount), 0) as revenue,
               COALESCE(
                   (SELECT SUM(d.quantity_received * d.unit_cost)
                    FROM stock_deliveries d
                    WHERE d.inventory_id = i.inventory_id), 0
               ) as total_cost
        FROM inventory i
        LEFT JOIN transactions t ON i.inventory_id = t.inventory_id
        GROUP BY i.inventory_id
        HAVING units_sold > 0
        ORDER BY revenue DESC
    ");
    while($r = $rows->fetch_assoc()) {
        $profit = $r['revenue'] - $r['total_cost'];
        $margin = $r['revenue'] > 0 ? round(($profit / $r['revenue']) * 100, 1) : 0;
        fputcsv($out, [
            $r['team_name'], $r['size'], $r['colour'], $r['category'],
            $r['units_sold'],
            number_format($r['revenue'], 2),
            number_format($r['total_cost'], 2),
            number_format($profit, 2),
            $margin . '%'
        ]);
    }
    fclose($out);
    exit();
}

include("header.php");

// Overall financials
$total_revenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM transactions")->fetch_assoc()['t'];
$total_cost      = $conn->query("SELECT COALESCE(SUM(quantity_received * unit_cost),0) as t FROM stock_deliveries")->fetch_assoc()['t'];
$gross_profit    = $total_revenue - $total_cost;
$margin_pct      = $total_revenue > 0 ? round(($gross_profit / $total_revenue) * 100, 1) : 0;

// This month
$month_revenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM transactions WHERE MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW())")->fetch_assoc()['t'];
$month_cost      = $conn->query("SELECT COALESCE(SUM(quantity_received * unit_cost),0) as t FROM stock_deliveries WHERE MONTH(delivery_date)=MONTH(NOW()) AND YEAR(delivery_date)=YEAR(NOW())")->fetch_assoc()['t'];
$month_profit    = $month_revenue - $month_cost;

// Per-item profit breakdown (only items that have been sold AND have delivery records)
$item_profit = $conn->query("
    SELECT i.team_name, i.size, i.colour, i.category,
           COALESCE(SUM(t.quantity_sold), 0) as units_sold,
           COALESCE(SUM(t.total_amount), 0) as revenue,
           COALESCE(
               (SELECT SUM(d.quantity_received * d.unit_cost)
                FROM stock_deliveries d
                WHERE d.inventory_id = i.inventory_id), 0
           ) as total_cost
    FROM inventory i
    LEFT JOIN transactions t ON i.inventory_id = t.inventory_id
    GROUP BY i.inventory_id
    HAVING units_sold > 0
    ORDER BY revenue DESC
");

// Monthly profit trend (last 6 months)
$monthly_data = [];
for($m = 5; $m >= 0; $m--) {
    $month = date('m', strtotime("-$m months"));
    $year  = date('Y', strtotime("-$m months"));
    $label = date('M Y', strtotime("-$m months"));
    $rev = (float)$conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM transactions WHERE MONTH(transaction_date)='$month' AND YEAR(transaction_date)='$year'")->fetch_assoc()['t'];
    $cst = (float)$conn->query("SELECT COALESCE(SUM(quantity_received*unit_cost),0) as t FROM stock_deliveries WHERE MONTH(delivery_date)='$month' AND YEAR(delivery_date)='$year'")->fetch_assoc()['t'];
    $monthly_data[] = ['label' => $label, 'revenue' => $rev, 'cost' => $cst, 'profit' => $rev - $cst];
}

// Category profit
$cat_profit = $conn->query("
    SELECT i.category,
           COALESCE(SUM(t.quantity_sold), 0) as units,
           COALESCE(SUM(t.total_amount), 0) as revenue
    FROM inventory i
    LEFT JOIN transactions t ON i.inventory_id = t.inventory_id
    GROUP BY i.category
    ORDER BY i.category
");

$cat_desc = ['A' => 'High Value (Originals)', 'B' => 'Medium (Tracksuits)', 'C' => 'Low Value (Accessories)'];
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Financial / Profit Report</h2>
        <div class="breadcrumb">Revenue vs procurement cost — gross profit & margin analysis</div>
    </div>
    <div class="topbar-right" style="display:flex;gap:10px;align-items:center">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
        <a href="financials_report.php?download=1" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download CSV
        </a>
    </div>
</div>

<div class="page-content">

<!-- KEY FIGURES -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card green">
        <div class="stat-card-top">
            <div class="stat-label">Total Revenue (All Time)</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px">KES <?php echo number_format($total_revenue, 2); ?></div>
        <div class="stat-sub">Total sales income</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #dc2626">
        <div class="stat-card-top">
            <div class="stat-label">Total Procurement Cost</div>
            <div class="stat-icon icon-red">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px;color:#dc2626">KES <?php echo number_format($total_cost, 2); ?></div>
        <div class="stat-sub">Total stock purchase cost</div>
    </div>
    <div class="stat-card" style="border-left:4px solid <?php echo $gross_profit >= 0 ? '#1a7a3e' : '#dc2626'; ?>">
        <div class="stat-card-top">
            <div class="stat-label">Gross Profit (All Time)</div>
            <div class="stat-icon <?php echo $gross_profit >= 0 ? 'icon-green' : 'icon-red'; ?>">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value" style="font-size:18px;color:<?php echo $gross_profit >= 0 ? '#1a7a3e' : '#dc2626'; ?>">
            <?php echo $gross_profit >= 0 ? '+' : ''; ?>KES <?php echo number_format($gross_profit, 2); ?>
        </div>
        <div class="stat-sub"><?php echo $gross_profit >= 0 ? '▲ PROFIT' : '▼ LOSS'; ?></div>
    </div>
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Gross Margin</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
            </div>
        </div>
        <div class="stat-value" style="color:<?php echo $margin_pct >= 30 ? '#1a7a3e' : ($margin_pct >= 10 ? '#e67700' : '#dc2626'); ?>">
            <?php echo $margin_pct; ?>%
        </div>
        <div class="stat-sub">This month: <?php echo $month_profit >= 0 ? '+' : ''; ?>KES <?php echo number_format($month_profit, 2); ?></div>
    </div>
</div>

<!-- MONTHLY TREND -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>Monthly Profit Trend — Last 6 Months</h3></div>
    <div class="card-body">
        <canvas id="profitChart" height="110"></canvas>
    </div>
</div>

<!-- CATEGORY PROFIT -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h3>Revenue by Category</h3></div>
    <table>
        <thead><tr><th>Category</th><th>Description</th><th>Units Sold</th><th>Revenue (KES)</th><th>% of Revenue</th></tr></thead>
        <tbody>
        <?php while($r = $cat_profit->fetch_assoc()):
            $pct = $total_revenue > 0 ? round(($r['revenue'] / $total_revenue) * 100, 1) : 0;
        ?>
            <tr>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td style="color:var(--gray-500);font-size:12px"><?php echo $cat_desc[$r['category']] ?? ''; ?></td>
                <td style="font-weight:700;font-family:'JetBrains Mono',monospace"><?php echo number_format($r['units']); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-ok)"><?php echo number_format($r['revenue'], 2); ?></td>
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

<!-- PER-ITEM PROFIT TABLE -->
<div class="card">
    <div class="card-header">
        <h3>Profit per Product Line</h3>
        <a href="financials_report.php?download=1" class="btn btn-secondary btn-sm">⬇ Download CSV</a>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Jersey / Team</th><th>Size</th><th>Colour</th><th>Cat.</th>
                <th>Units Sold</th><th>Revenue (KES)</th><th>Procurement Cost (KES)</th>
                <th>Gross Profit (KES)</th><th>Margin %</th>
            </tr>
        </thead>
        <tbody>
        <?php if($item_profit->num_rows == 0): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:30px">No sales recorded yet.</td></tr>
        <?php else: while($r = $item_profit->fetch_assoc()):
            $profit = $r['revenue'] - $r['total_cost'];
            $margin = $r['revenue'] > 0 ? round(($profit / $r['revenue']) * 100, 1) : 0;
            $margin_color = $margin >= 30 ? 'var(--green-ok)' : ($margin >= 10 ? '#e67700' : '#dc2626');
        ?>
            <tr>
                <td style="font-weight:700"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><?php echo $r['size']; ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['colour']); ?></td>
                <td><span class="badge badge-<?php echo $r['category']; ?>"><?php echo $r['category']; ?></span></td>
                <td style="font-weight:700;font-family:'JetBrains Mono',monospace"><?php echo number_format($r['units_sold']); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;color:var(--green-ok);font-weight:700"><?php echo number_format($r['revenue'], 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;color:#dc2626"><?php echo number_format($r['total_cost'], 2); ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-weight:800;color:<?php echo $profit >= 0 ? 'var(--green-ok)' : '#dc2626'; ?>">
                    <?php echo $profit >= 0 ? '+' : ''; ?><?php echo number_format($profit, 2); ?>
                </td>
                <td>
                    <span style="font-weight:800;color:<?php echo $margin_color; ?>;font-family:'JetBrains Mono',monospace">
                        <?php echo $margin; ?>%
                    </span>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>

<script>
const profitCtx = document.getElementById('profitChart').getContext('2d');
new Chart(profitCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($monthly_data, 'label')); ?>,
        datasets: [
            {
                label: 'Revenue',
                data: <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>,
                backgroundColor: 'rgba(26,122,62,0.7)',
                borderColor: '#1a7a3e', borderWidth:1, borderRadius:4
            },
            {
                label: 'Cost',
                data: <?php echo json_encode(array_column($monthly_data, 'cost')); ?>,
                backgroundColor: 'rgba(220,38,38,0.6)',
                borderColor: '#dc2626', borderWidth:1, borderRadius:4
            },
            {
                label: 'Gross Profit',
                data: <?php echo json_encode(array_column($monthly_data, 'profit')); ?>,
                type: 'line',
                borderColor: '#e67700', backgroundColor: 'rgba(230,119,0,0.1)',
                borderWidth:2.5, pointRadius:4, fill:true, tension:0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { family: 'DM Sans', size: 12 } } } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { family: 'DM Mono', size: 10 } } },
            x: { grid: { display: false }, ticks: { font: { family: 'DM Sans', size: 11 } } }
        }
    }
});
</script>

<?php include("footer.php"); ?>