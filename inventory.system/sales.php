<?php
session_start();
include("connection.php");

$role = $_SESSION["role"];
$msg = $_GET['msg'] ?? '';

// Delete sale (Manager only) - BEFORE HTML
if(isset($_GET['delete']) && $role == 'Manager') {
    $del_id = (int)$_GET['delete'];
    $sale = $conn->query("SELECT inventory_id, quantity_sold FROM transactions WHERE transaction_id = $del_id")->fetch_assoc();
    if($sale) {
        $conn->query("UPDATE inventory SET quantity = quantity + {$sale['quantity_sold']} WHERE inventory_id = {$sale['inventory_id']}");
        $conn->query("DELETE FROM transactions WHERE transaction_id = $del_id");
    }
    header("Location: sales.php?msg=deleted"); exit();
}

include("header.php");

$inventory = $conn->query("SELECT inventory_id, team_name, size, colour, category, quantity, price FROM inventory WHERE quantity > 0 ORDER BY team_name");
$sales = $conn->query("SELECT t.transaction_id, t.transaction_date, i.team_name, i.size, i.colour, t.quantity_sold, t.sale_price, t.total_amount, u.full_name FROM transactions t JOIN inventory i ON t.inventory_id = i.inventory_id JOIN users u ON t.user_id = u.user_id ORDER BY t.transaction_date DESC LIMIT 50");

// Build inventory array for JS
$inv_data = [];
$inventory->data_seek(0);
while($i = $inventory->fetch_assoc()) {
    $inv_data[$i['inventory_id']] = ['price' => $i['price'], 'stock' => $i['quantity'], 'name' => $i['team_name'].' — '.$i['size'].' / '.$i['colour'].' ('.$i['quantity'].' in stock)'];
}
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>Sales</h2>
        <div class="breadcrumb">Record sales and view transaction history</div>
    </div>
</div>

<div class="page-content">

<?php if($msg == 'recorded'): ?><div class="alert alert-success">✓ Sale recorded and stock updated.</div><?php endif; ?>
<?php if($msg == 'deleted'): ?><div class="alert alert-warning">Sale removed and stock restored.</div><?php endif; ?>
<?php if($msg == 'nostock'): ?><div class="alert alert-danger">Not enough stock for one or more items.</div><?php endif; ?>
<?php if($msg == 'error'): ?><div class="alert alert-danger">Error recording sale. Please try again.</div><?php endif; ?>

<div class="two-col" style="margin-bottom:24px">

    <!-- RECORD SALE FORM -->
    <div class="card">
        <div class="card-header">
            <h3>Record New Sale</h3>
            <span class="badge badge-gold">Multi-item</span>
        </div>
        <div class="card-body">
            <form action="sale_process.php" method="POST" id="sale-form">

                <div class="form-group" style="margin-bottom:16px">
                    <label>Customer Name (optional)</label>
                    <input type="text" name="customer_name" placeholder="Walk-in customer or name">
                </div>

                <!-- ITEMS TABLE -->
                <div style="margin-bottom:14px">
                    <label style="display:block;margin-bottom:8px">Items *</label>
                    <div style="border:1.5px solid var(--gray-200);border-radius:8px;overflow:hidden">
                        <table style="width:100%;border-collapse:collapse" id="items-table">
                            <thead>
                                <tr style="background:var(--off-white)">
                                    <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.5px;text-align:left">Jersey</th>
                                    <th style="padding:8px 10px;font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;width:80px">Qty</th>
                                    <th style="padding:8px 10px;font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;width:110px">Unit Price</th>
                                    <th style="padding:8px 10px;font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;width:100px">Subtotal</th>
                                    <th style="padding:8px 10px;width:36px"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <!-- rows injected by JS -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" onclick="addRow()" style="margin-top:8px;display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--gold);background:var(--gold-dim);border:1.5px dashed var(--gold-border);border-radius:6px;padding:7px 14px;cursor:pointer;font-family:'Outfit',sans-serif;width:100%;justify-content:center">
                        + Add Another Jersey
                    </button>
                </div>

                <!-- GRAND TOTAL -->
                <div style="background:var(--black);border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:#9ca3af;font-weight:500">Grand Total</span>
                    <span id="grand-total" style="font-size:24px;font-weight:800;font-family:'JetBrains Mono',monospace;color:var(--gold)">KES 0.00</span>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4H9m0 0a2 2 0 000 4h6a2 2 0 000-4M9 3h6"/></svg>
                    Record Sale
                </button>
            </form>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div style="display:flex;flex-direction:column;gap:16px">
        <?php
        $today_total = $conn->query("SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as s FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch_assoc();
        $week_total  = $conn->query("SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as s FROM transactions WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc();
        ?>
        <div class="stat-card gold">
            <div class="stat-card-top">
                <div class="stat-label">Today's Sales</div>
                <div class="stat-icon icon-gold">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                </div>
            </div>
            <div class="stat-value"><?php echo $today_total['c']; ?></div>
            <div class="stat-sub">KES <?php echo number_format($today_total['s'],2); ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-card-top">
                <div class="stat-label">This Week</div>
                <div class="stat-icon icon-blue">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                </div>
            </div>
            <div class="stat-value"><?php echo $week_total['c']; ?></div>
            <div class="stat-sub">KES <?php echo number_format($week_total['s'],2); ?></div>
        </div>
        <div class="card" style="flex:1">
            <div class="card-header"><h3>Sales by Category</h3></div>
            <div class="card-body"><canvas id="catChart"></canvas></div>
        </div>
    </div>
</div>

<!-- TRANSACTION HISTORY -->
<div class="card">
    <div class="card-header"><h3>Transaction History (Last 50)</h3></div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Date & Time</th><th>Jersey</th><th>Size</th><th>Colour</th>
                <th>Qty</th><th>Unit Price</th><th>Total (KES)</th><th>Processed By</th>
                <?php if($role == 'Manager'): ?><th>Action</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if($sales->num_rows == 0): ?>
            <tr><td colspan="9" style="text-align:center;color:var(--gray-500);padding:30px">No sales recorded yet.</td></tr>
        <?php else: while($r = $sales->fetch_assoc()): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:11px"><?php echo date('d M Y H:i', strtotime($r['transaction_date'])); ?></td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?></td>
                <td><span class="badge badge-blue"><?php echo $r['size']; ?></span></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['colour']); ?></td>
                <td><?php echo $r['quantity_sold']; ?></td>
                <td style="font-family:'JetBrains Mono',monospace">KES <?php echo number_format($r['sale_price'],2); ?></td>
                <td style="font-weight:700;font-family:'JetBrains Mono',monospace">KES <?php echo number_format($r['total_amount'],2); ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <?php if($role == 'Manager'): ?>
                <td><a href="sales.php?delete=<?php echo $r['transaction_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this sale? Stock will be restored.')">Delete</a></td>
                <?php endif; ?>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>
</div>

<script>
const INV = <?php echo json_encode($inv_data); ?>;

// Build jersey options HTML
function jerseyOptions(selectedId) {
    let html = '<option value="">— Choose jersey —</option>';
    for(const [id, d] of Object.entries(INV)) {
        html += `<option value="${id}" data-price="${d.price}" data-stock="${d.stock}" ${id==selectedId?'selected':''}>${d.name}</option>`;
    }
    return html;
}

let rowCount = 0;

function addRow(invId, qty, price) {
    rowCount++;
    const rid = rowCount;
    const tr = document.createElement('tr');
    tr.id = 'row-' + rid;
    tr.style.borderBottom = '1px solid #f1f3f5';
    tr.innerHTML = `
        <td style="padding:8px 12px">
            <select name="items[${rid}][inventory_id]" onchange="rowChanged(${rid})" required style="width:100%;padding:7px 9px;border:1.5px solid #e9ecef;border-radius:6px;font-size:12px;font-family:'Outfit',sans-serif">
                ${jerseyOptions(invId||'')}
            </select>
        </td>
        <td style="padding:8px 10px">
            <input type="number" name="items[${rid}][quantity]" id="qty-${rid}" value="${qty||1}" min="1" oninput="calcRow(${rid})"
                style="width:68px;padding:7px 9px;border:1.5px solid #e9ecef;border-radius:6px;font-size:12px;font-family:'JetBrains Mono',monospace;text-align:center">
        </td>
        <td style="padding:8px 10px">
            <input type="number" name="items[${rid}][sale_price]" id="price-${rid}" value="${price||''}" min="0" step="0.01" oninput="calcRow(${rid})" placeholder="0.00"
                style="width:95px;padding:7px 9px;border:1.5px solid #e9ecef;border-radius:6px;font-size:12px;font-family:'JetBrains Mono',monospace">
        </td>
        <td style="padding:8px 10px;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;color:#111214" id="sub-${rid}">KES 0.00</td>
        <td style="padding:8px 10px;text-align:center">
            <button type="button" onclick="removeRow(${rid})" style="background:#fff5f5;border:1px solid #ffc9c9;border-radius:5px;color:#e03131;width:26px;height:26px;cursor:pointer;font-size:14px;line-height:1;font-family:'Outfit',sans-serif" title="Remove">×</button>
        </td>
    `;
    document.getElementById('items-body').appendChild(tr);
    if(invId) calcRow(rid);
    else rowChanged(rid);
    calcGrandTotal();
}

function removeRow(rid) {
    const tbody = document.getElementById('items-body');
    if(tbody.children.length <= 1) return; // keep at least 1 row
    document.getElementById('row-' + rid).remove();
    calcGrandTotal();
}

function rowChanged(rid) {
    const sel = document.querySelector(`select[name="items[${rid}][inventory_id]"]`);
    const opt = sel.options[sel.selectedIndex];
    const price = opt.getAttribute('data-price') || '';
    document.getElementById('price-' + rid).value = price;
    calcRow(rid);
}

function calcRow(rid) {
    const qty   = parseFloat(document.getElementById('qty-' + rid).value) || 0;
    const price = parseFloat(document.getElementById('price-' + rid).value) || 0;
    const sub   = qty * price;
    document.getElementById('sub-' + rid).textContent = 'KES ' + sub.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    calcGrandTotal();
}

function calcGrandTotal() {
    let total = 0;
    document.querySelectorAll('[id^="sub-"]').forEach(el => {
        total += parseFloat(el.textContent.replace('KES ','').replace(/,/g,'')) || 0;
    });
    document.getElementById('grand-total').textContent = 'KES ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Start with one row
addRow();

// Validate before submit — no duplicate jerseys
document.getElementById('sale-form').addEventListener('submit', function(e) {
    const selects = document.querySelectorAll('#items-body select');
    const ids = Array.from(selects).map(s => s.value);
    const dupes = ids.filter((v,i) => v && ids.indexOf(v) !== i);
    if(dupes.length > 0) {
        e.preventDefault();
        alert('You have selected the same jersey more than once. Please combine them into one row or remove the duplicate.');
        return;
    }
    const empty = ids.filter(v => !v);
    if(empty.length > 0) {
        e.preventDefault();
        alert('Please select a jersey for every row, or remove empty rows.');
    }
});

// Category chart
<?php
$cats = $conn->query("SELECT i.category, SUM(t.quantity_sold) as total FROM transactions t JOIN inventory i ON t.inventory_id = i.inventory_id GROUP BY i.category");
$cat_labels = []; $cat_data = []; $cat_colors = [];
$color_map = ['A'=>'#e03131','B'=>'#e67700','C'=>'#2f9e44'];
while($c = $cats->fetch_assoc()) {
    $cat_labels[] = 'Category '.$c['category'];
    $cat_data[]   = (int)$c['total'];
    $cat_colors[] = $color_map[$c['category']] ?? '#6b7280';
}
?>
new Chart(document.getElementById('catChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($cat_labels ?: ['No data']); ?>,
        datasets: [{ data: <?php echo json_encode($cat_data ?: [1]); ?>, backgroundColor: <?php echo json_encode($cat_colors ?: ['#e9ecef']); ?>, borderWidth: 2 }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Outfit', size: 12 } } } } }
});
</script>

<?php include("footer.php"); ?>
