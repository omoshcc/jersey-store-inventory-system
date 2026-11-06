<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != 'Manager') {
    header("Location: dashboard.php"); exit();
}

// Handle recording supplier payment - BEFORE HTML
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_supplier'])) {
    $supplier_id   = (int)$_POST['supplier_id'];
    $delivery_id   = (int)$_POST['delivery_id'];
    $amount_paid   = (float)$_POST['amount_paid'];
    $payment_date  = $conn->real_escape_string($_POST['payment_date']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);

    $stmt = $conn->prepare("INSERT INTO supplier_payments (supplier_id, delivery_id, amount_paid, payment_date, payment_method) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iidss", $supplier_id, $delivery_id, $amount_paid, $payment_date, $payment_method);
    if($stmt->execute()) {
        header("Location: financials.php?msg=paid");
    } else {
        header("Location: financials.php?msg=error");
    }
    exit();
}

include("header.php");

$msg = $_GET['msg'] ?? '';

// ---- KEY FINANCIAL FIGURES ----
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) as total FROM transactions")->fetch_assoc()['total'];
$total_paid_out = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as total FROM supplier_payments")->fetch_assoc()['total'];
$net_profit = $total_revenue - $total_paid_out;
$is_profit = $net_profit >= 0;

// This month
$month_revenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) as total FROM transactions WHERE MONTH(transaction_date)=MONTH(NOW()) AND YEAR(transaction_date)=YEAR(NOW())")->fetch_assoc()['total'];
$month_paid = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as total FROM supplier_payments WHERE MONTH(payment_date)=MONTH(NOW()) AND YEAR(payment_date)=YEAR(NOW())")->fetch_assoc()['total'];
$month_profit = $month_revenue - $month_paid;

// Monthly chart - last 6 months
$chart_months = []; $chart_revenue = []; $chart_expenses = [];
for($i = 5; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $year  = date('Y', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $rev = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM transactions WHERE MONTH(transaction_date)='$month' AND YEAR(transaction_date)='$year'")->fetch_assoc()['t'];
    $exp = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as t FROM supplier_payments WHERE MONTH(payment_date)='$month' AND YEAR(payment_date)='$year'")->fetch_assoc()['t'];
    $chart_months[]   = $label;
    $chart_revenue[]  = (float)$rev;
    $chart_expenses[] = (float)$exp;
}

// Unpaid deliveries (deliveries with no payment recorded yet)
// Supplier balances — group all unpaid/partial deliveries by supplier
$supplier_balances = $conn->query("
    SELECT s.supplier_id, s.supplier_name,
           COUNT(d.delivery_id) as delivery_count,
           SUM(d.quantity_received * d.unit_cost) as total_cost,
           COALESCE(SUM(
               (SELECT COALESCE(SUM(sp.amount_paid),0) FROM supplier_payments sp WHERE sp.delivery_id = d.delivery_id)
           ),0) as total_paid,
           SUM(d.quantity_received * d.unit_cost) - COALESCE(SUM(
               (SELECT COALESCE(SUM(sp.amount_paid),0) FROM supplier_payments sp WHERE sp.delivery_id = d.delivery_id)
           ),0) as balance_due
    FROM stock_deliveries d
    JOIN suppliers s ON d.supplier_id = s.supplier_id
    GROUP BY s.supplier_id, s.supplier_name
    HAVING balance_due > 0
    ORDER BY balance_due DESC
");

// Unpaid individual deliveries (for the pay button detail)
$unpaid = $conn->query("
    SELECT d.delivery_id, d.delivery_date, d.quantity_received,
           d.unit_cost,
           i.team_name, i.size,
           s.supplier_id, s.supplier_name,
           (d.quantity_received * d.unit_cost) as total_cost,
           COALESCE((SELECT SUM(amount_paid) FROM supplier_payments WHERE delivery_id = d.delivery_id), 0) as total_paid,
           (d.quantity_received * d.unit_cost) - COALESCE((SELECT SUM(amount_paid) FROM supplier_payments WHERE delivery_id = d.delivery_id), 0) as remaining
    FROM stock_deliveries d
    JOIN inventory i ON d.inventory_id = i.inventory_id
    JOIN suppliers s ON d.supplier_id = s.supplier_id
    HAVING remaining > 0
    ORDER BY s.supplier_name, d.delivery_date DESC
");

// Group unpaid deliveries by supplier for easy lookup
$deliveries_by_supplier = [];
while($r = $unpaid->fetch_assoc()) {
    $deliveries_by_supplier[$r['supplier_id']][] = $r;
}
$payments = $conn->query("
    SELECT sp.*, s.supplier_name, i.team_name, i.size, d.quantity_received
    FROM supplier_payments sp
    JOIN suppliers s ON sp.supplier_id = s.supplier_id
    JOIN stock_deliveries d ON sp.delivery_id = d.delivery_id
    JOIN inventory i ON d.inventory_id = i.inventory_id
    ORDER BY sp.payment_date DESC LIMIT 30
");

// Top spending by supplier
$top_suppliers = $conn->query("
    SELECT s.supplier_name, SUM(sp.amount_paid) as total_paid, COUNT(*) as payments
    FROM supplier_payments sp
    JOIN suppliers s ON sp.supplier_id = s.supplier_id
    GROUP BY s.supplier_id ORDER BY total_paid DESC LIMIT 5
");
?>

<div class="topbar">
    <div><h2>Financial Overview</h2><div class="sub">Revenue received vs supplier payments — profit & loss</div></div>
</div>

<div class="page-content">

<?php if($msg == 'paid'): ?><div class="alert alert-success">✓ Supplier payment recorded successfully.</div><?php endif; ?>
<?php if($msg == 'error'): ?><div class="alert alert-danger">Failed to record payment. Please try again.</div><?php endif; ?>

<!-- KEY FIGURES -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="icon icon-green">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
        <div class="label">Total Revenue (All Time)</div>
        <div class="value" style="font-size:22px">KES <?php echo number_format($total_revenue, 2); ?></div>
        <div class="change">Money received from sales</div>
    </div>
    <div class="stat-card">
        <div class="icon icon-red">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
        </div>
        <div class="label">Total Paid to Suppliers</div>
        <div class="value" style="font-size:22px">KES <?php echo number_format($total_paid_out, 2); ?></div>
        <div class="change">Money paid out for stock</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid <?php echo $is_profit ? '#1a7a3e' : '#dc2626'; ?>">
        <div class="icon <?php echo $is_profit ? 'icon-green' : 'icon-red'; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="label">Net Profit / Loss (All Time)</div>
        <div class="value" style="font-size:22px;color:<?php echo $is_profit ? '#1a7a3e' : '#dc2626'; ?>">
            <?php echo $is_profit ? '+' : ''; ?>KES <?php echo number_format($net_profit, 2); ?>
        </div>
        <div class="change"><?php echo $is_profit ? '▲ Currently at PROFIT' : '▼ Currently at LOSS'; ?></div>
    </div>
    <div class="stat-card" style="border-left: 4px solid <?php echo $month_profit >= 0 ? '#1a7a3e' : '#dc2626'; ?>">
        <div class="icon icon-amber">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="label">This Month — Net</div>
        <div class="value" style="font-size:22px;color:<?php echo $month_profit >= 0 ? '#1a7a3e' : '#dc2626'; ?>">
            <?php echo $month_profit >= 0 ? '+' : ''; ?>KES <?php echo number_format($month_profit, 2); ?>
        </div>
        <div class="change">Revenue: KES <?php echo number_format($month_revenue,2); ?> | Paid: KES <?php echo number_format($month_paid,2); ?></div>
    </div>
</div>

<!-- CHART + TOP SUPPLIERS -->
<div class="two-col" style="margin-bottom:24px">
    <div class="card">
        <div class="card-header"><h3>Revenue vs Supplier Payments — Last 6 Months</h3></div>
        <div class="card-body"><canvas id="finChart" height="130"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Top Suppliers by Amount Paid</h3></div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Supplier</th><th>Payments</th><th>Total Paid (KES)</th></tr></thead>
            <tbody>
            <?php if($top_suppliers->num_rows == 0): ?>
                <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px">No payments recorded yet</td></tr>
            <?php else: while($r = $top_suppliers->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:600"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                    <td><?php echo $r['payments']; ?></td>
                    <td style="font-family:'DM Mono',monospace;font-weight:700;color:var(--red)"><?php echo number_format($r['total_paid'],2); ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- RECORD PAYMENT + UNPAID DELIVERIES -->
<div class="two-col" style="margin-bottom:24px">
    <!-- Supplier balances -->
    <div class="card">
        <div class="card-header">
            <h3>Balance Owed per Supplier</h3>
            <?php if($supplier_balances && $supplier_balances->num_rows > 0): ?>
            <span class="badge badge-red"><?php echo $supplier_balances->num_rows; ?> suppliers</span>
            <?php endif; ?>
        </div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Supplier</th><th>Deliveries</th><th>Total Cost</th><th>Paid So Far</th><th>Balance Due</th><th>Pay</th></tr></thead>
            <tbody>
            <?php if(!$supplier_balances || $supplier_balances->num_rows == 0): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--gray-500);padding:20px">All suppliers fully paid ✓</td></tr>
            <?php else: while($sb = $supplier_balances->fetch_assoc()): ?>
                <tr style="cursor:pointer" onclick="toggleDeliveries(<?php echo $sb['supplier_id']; ?>)">
                    <td style="font-weight:700"><?php echo htmlspecialchars($sb['supplier_name']); ?> <span style="font-size:10px;color:var(--gray-500)" id="arrow-<?php echo $sb['supplier_id']; ?>">▼</span></td>
                    <td><span class="badge badge-blue"><?php echo $sb['delivery_count']; ?></span></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px">KES <?php echo number_format($sb['total_cost'],2); ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--green-ok)">KES <?php echo number_format($sb['total_paid'],2); ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:800;color:var(--red);font-size:14px">KES <?php echo number_format($sb['balance_due'],2); ?></td>
                    <td><span class="badge badge-gold">Click to expand</span></td>
                </tr>
                <!-- Expandable delivery rows for this supplier -->
                <tr id="deliveries-<?php echo $sb['supplier_id']; ?>" style="display:none">
                    <td colspan="6" style="padding:0;background:var(--off-white)">
                        <table style="width:100%;border-collapse:collapse">
                            <thead><tr style="background:#f1f3f5">
                                <th style="padding:7px 16px 7px 32px;font-size:10px;color:var(--gray-500);font-weight:700;text-transform:uppercase">Date</th>
                                <th style="padding:7px 10px;font-size:10px;color:var(--gray-500);font-weight:700;text-transform:uppercase">Jersey</th>
                                <th style="padding:7px 10px;font-size:10px;color:var(--gray-500);font-weight:700;text-transform:uppercase">Qty</th>
                                <th style="padding:7px 10px;font-size:10px;color:var(--gray-500);font-weight:700;text-transform:uppercase">Total Cost</th>
                                <th style="padding:7px 10px;font-size:10px;color:var(--gray-500);font-weight:700;text-transform:uppercase">Paid</th>
                                <th style="padding:7px 10px;font-size:10px;color:var(--gray-500);font-weight:700;text-transform:uppercase">Remaining</th>
                                <th style="padding:7px 10px;font-size:10px;color:var(--gray-500);font-weight:700;text-transform:uppercase">Pay</th>
                            </tr></thead>
                            <tbody>
                            <?php
                            $rows = $deliveries_by_supplier[$sb['supplier_id']] ?? [];
                            foreach($rows as $r):
                            ?>
                                <tr style="border-bottom:1px solid #e9ecef">
                                    <td style="padding:7px 16px 7px 32px;font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo date('d M Y', strtotime($r['delivery_date'])); ?></td>
                                    <td style="padding:7px 10px;font-size:12px;font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?> <?php echo $r['size']; ?></td>
                                    <td style="padding:7px 10px;font-size:12px"><?php echo $r['quantity_received']; ?></td>
                                    <td style="padding:7px 10px;font-family:'JetBrains Mono',monospace;font-size:11px">KES <?php echo number_format($r['total_cost'],2); ?></td>
                                    <td style="padding:7px 10px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--green-ok)"><?php echo $r['total_paid']>0 ? 'KES '.number_format($r['total_paid'],2) : '—'; ?></td>
                                    <td style="padding:7px 10px;font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:700;color:var(--red)">KES <?php echo number_format($r['remaining'],2); ?></td>
                                    <td style="padding:7px 10px">
                                        <button onclick="openPayModal(<?php echo $r['delivery_id']; ?>,<?php echo $r['supplier_id']; ?>,'<?php echo htmlspecialchars($r['supplier_name']); ?>',<?php echo $r['remaining']; ?>)" class="btn btn-primary btn-sm">Pay</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Payment history -->
    <div class="card">
        <div class="card-header"><h3>Recent Payments to Suppliers</h3></div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Date</th><th>Supplier</th><th>Jersey</th><th>Method</th><th>Amount (KES)</th></tr></thead>
            <tbody>
            <?php if($payments->num_rows == 0): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">No payments recorded yet</td></tr>
            <?php else: while($r = $payments->fetch_assoc()): ?>
                <tr>
                    <td style="font-size:12px;font-family:'DM Mono',monospace"><?php echo date('d M Y', strtotime($r['payment_date'])); ?></td>
                    <td style="font-weight:600"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                    <td style="font-size:12px"><?php echo htmlspecialchars($r['team_name']); ?> <?php echo $r['size']; ?></td>
                    <td><span class="badge badge-green"><?php echo htmlspecialchars($r['payment_method']); ?></span></td>
                    <td style="font-family:'DM Mono',monospace;font-weight:700;color:var(--red)">-<?php echo number_format($r['amount_paid'],2); ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

</div>

<!-- PAYMENT MODAL -->
<div id="pay-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:28px;width:400px;max-width:90%">
        <h3 style="margin-bottom:4px;font-size:16px">Record Supplier Payment</h3>
        <p id="modal-supplier-name" style="color:var(--muted);font-size:13px;margin-bottom:4px"></p>
        <p id="modal-balance-label" style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:20px"></p>
        <form method="POST">
            <input type="hidden" name="pay_supplier" value="1">
            <input type="hidden" name="delivery_id" id="modal-delivery-id">
            <input type="hidden" name="supplier_id" id="modal-supplier-id">
            <div class="form-group" style="margin-bottom:14px">
                <label>Amount Paying Now (KES) *</label>
                <input type="number" name="amount_paid" id="modal-amount" required min="0.01" step="0.01">
                <span id="modal-max-hint" style="font-size:11px;color:var(--gray-500);margin-top:3px"></span>
            </div>
            <div class="form-group" style="margin-bottom:14px">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group" style="margin-bottom:20px">
                <label>Payment Method *</label>
                <select name="payment_method" required>
                    <option value="M-Pesa">M-Pesa</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Confirm Payment</button>
                <button type="button" onclick="closePayModal()" class="btn btn-secondary" style="flex:1;justify-content:center">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Finance chart
const finCtx = document.getElementById('finChart').getContext('2d');
new Chart(finCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_months); ?>,
        datasets: [
            {
                label: 'Revenue Received',
                data: <?php echo json_encode($chart_revenue); ?>,
                backgroundColor: 'rgba(26,122,62,0.7)',
                borderColor: '#1a7a3e',
                borderWidth: 1,
                borderRadius: 4
            },
            {
                label: 'Paid to Suppliers',
                data: <?php echo json_encode($chart_expenses); ?>,
                backgroundColor: 'rgba(220,38,38,0.6)',
                borderColor: '#dc2626',
                borderWidth: 1,
                borderRadius: 4
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

// Modal
function openPayModal(deliveryId, supplierId, supplierName, remainingBalance) {
    document.getElementById('modal-delivery-id').value = deliveryId;
    document.getElementById('modal-supplier-id').value = supplierId;
    document.getElementById('modal-supplier-name').textContent = 'Supplier: ' + supplierName;
    document.getElementById('modal-balance-label').textContent = 'Balance remaining: KES ' + remainingBalance.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('modal-amount').value = remainingBalance.toFixed(2);
    document.getElementById('modal-amount').max = remainingBalance;
    document.getElementById('modal-max-hint').textContent = 'Enter partial or full amount. Max: KES ' + remainingBalance.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('pay-modal').style.display = 'flex';
}
function toggleDeliveries(supplierId) {
    const row   = document.getElementById('deliveries-' + supplierId);
    const arrow = document.getElementById('arrow-' + supplierId);
    if(!row) return;
    const isHidden = row.style.display === 'none' || row.style.display === '';
    row.style.display   = isHidden ? 'table-row' : 'none';
    if(arrow) arrow.textContent = isHidden ? '▲' : '▼';
}
function closePayModal() {
    document.getElementById('pay-modal').style.display = 'none';
}
</script>

<?php include("footer.php"); ?>
