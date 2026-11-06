<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ['Manager','Storekeeper'])) {
    header("Location: dashboard.php"); exit();
}

$msg = $_GET['msg'] ?? '';
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
$inventory = $conn->query("SELECT inventory_id, team_name, size, colour FROM inventory ORDER BY team_name");
$deliveries = $conn->query("SELECT d.delivery_id, d.delivery_date, d.quantity_received, d.unit_cost, i.team_name, i.size, s.supplier_name, u.full_name FROM stock_deliveries d JOIN inventory i ON d.inventory_id = i.inventory_id JOIN suppliers s ON d.supplier_id = s.supplier_id JOIN users u ON d.received_by = u.user_id ORDER BY d.delivery_date DESC LIMIT 30");

include("header.php");
if(!is_dir("uploads")) mkdir("uploads", 0755, true);
?>

<div class="topbar">
    <div><h2>Add Stock</h2><div class="sub">Record incoming deliveries — existing or new jerseys</div></div>
</div>

<div class="page-content">

<?php if($msg == 'added'): ?><div class="alert alert-success">✓ Delivery recorded and stock updated.</div><?php endif; ?>
<?php if($msg == 'added_new'): ?><div class="alert alert-success">✓ New jersey added to inventory and delivery recorded.</div><?php endif; ?>
<?php if($msg == 'error'): ?><div class="alert alert-danger">Failed to record. Please check all fields and try again.</div><?php endif; ?>

<div class="two-col">
    <div class="card">
        <div class="card-header"><h3>Record Delivery</h3></div>
        <div class="card-body">

            <!-- TAB TOGGLE -->
            <div style="display:flex;gap:0;margin-bottom:22px;border:1px solid var(--border);border-radius:6px;overflow:hidden">
                <button id="btn-existing" onclick="switchMode('existing')"
                    style="flex:1;padding:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:var(--green);color:#fff;font-family:'DM Sans',sans-serif;transition:all 0.15s">
                    ↩ Existing Jersey
                </button>
                <button id="btn-new" onclick="switchMode('new')"
                    style="flex:1;padding:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:#f3f4f6;color:var(--muted);font-family:'DM Sans',sans-serif;transition:all 0.15s">
                    + New Jersey
                </button>
            </div>

            <!-- ===== EXISTING JERSEY FORM ===== -->
            <form id="form-existing" action="stock_in_process.php" method="POST">
                <input type="hidden" name="mode" value="existing">

                <div class="form-group" style="margin-bottom:14px">
                    <label>Select Jersey *</label>
                    <select name="inventory_id" required>
                        <option value="">— Choose jersey —</option>
                        <?php while($i = $inventory->fetch_assoc()): ?>
                        <option value="<?php echo $i['inventory_id']; ?>">
                            <?php echo htmlspecialchars($i['team_name']); ?> — <?php echo $i['size']; ?> / <?php echo htmlspecialchars($i['colour']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:14px">
                    <label>Supplier *</label>
                    <select name="supplier_id" required>
                        <option value="">— Select supplier —</option>
                        <?php $suppliers->data_seek(0); while($s = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-grid" style="margin-bottom:14px">
                    <div class="form-group">
                        <label>Quantity Received *</label>
                        <input type="number" name="quantity_received" id="e-qty" required min="1" placeholder="0" oninput="calcCost('e')">
                    </div>
                    <div class="form-group">
                        <label>Purchase Price / Unit (KES) *</label>
                        <input type="number" name="unit_cost" id="e-cost" required min="0" step="0.01" placeholder="Price paid to supplier" oninput="calcCost('e')">
                    </div>
                </div>

                <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:6px;padding:10px 14px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--muted)">Total Cost to Supplier</span>
                    <span id="e-total" style="font-size:18px;font-weight:700;font-family:'DM Mono',monospace;color:var(--red)">KES 0.00</span>
                </div>

                <div class="form-group" style="margin-bottom:16px">
                    <label>Delivery Date *</label>
                    <input type="date" name="delivery_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Record Delivery</button>
            </form>

            <!-- ===== NEW JERSEY FORM ===== -->
            <form id="form-new" action="stock_in_process.php" method="POST" enctype="multipart/form-data" style="display:none">
                <input type="hidden" name="mode" value="new">

                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">
                    Jersey Details
                </div>

                <div class="form-grid" style="margin-bottom:14px">
                    <div class="form-group">
                        <label>Team Name *</label>
                        <input type="text" name="team_name" required placeholder="e.g. Arsenal, Chelsea">
                    </div>
                    <div class="form-group">
                        <label>Colour *</label>
                        <input type="text" name="colour" required placeholder="e.g. Red/White">
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom:14px">
                    <div class="form-group">
                        <label>Size *</label>
                        <select name="size" required>
                            <option value="">Select size</option>
                            <option>XS</option><option>S</option><option>M</option>
                            <option>L</option><option>XL</option><option>XXL</option><option>XXXL</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category (ABC) *</label>
                        <select name="category" required>
                            <option value="A">A — High Value (Originals)</option>
                            <option value="B" selected>B — Medium (Tracksuits)</option>
                            <option value="C">C — Low Value (Accessories)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom:14px">
                    <div class="form-group">
                        <label>Selling Price / Unit (KES) *</label>
                        <input type="number" name="price" required min="0" step="0.01" placeholder="Price you sell at">
                    </div>
                    <div class="form-group">
                        <label>Reorder Point *</label>
                        <input type="number" name="reorder_point" required min="1" value="5">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:16px">
                    <label>Jersey Photo (optional)</label>
                    <div id="photo-drop" onclick="document.getElementById('n-photo').click()"
                        style="border:2px dashed var(--border);border-radius:6px;padding:14px;text-align:center;cursor:pointer;background:#fafafa;transition:border 0.2s">
                        <svg width="28" height="28" fill="none" stroke="#9ca3af" stroke-width="1.5" viewBox="0 0 24 24" style="display:block;margin:0 auto 6px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        <div id="photo-label" style="font-size:12px;color:var(--muted)">Click to upload jersey photo</div>
                        <div style="font-size:11px;color:#9ca3af;margin-top:2px">JPG, PNG or WEBP — max 5MB</div>
                        <img id="photo-preview" src="" style="display:none;max-width:100px;max-height:100px;margin:8px auto 0;border-radius:4px;object-fit:cover">
                    </div>
                    <input type="file" id="n-photo" name="jersey_photo" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewPhoto(this)">
                </div>

                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)">
                    Delivery Details
                </div>

                <div class="form-group" style="margin-bottom:14px">
                    <label>Supplier *</label>
                    <select name="supplier_id" required>
                        <option value="">— Select supplier —</option>
                        <?php $suppliers->data_seek(0); while($s = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-grid" style="margin-bottom:14px">
                    <div class="form-group">
                        <label>Quantity Received *</label>
                        <input type="number" name="quantity_received" id="n-qty" required min="1" placeholder="0" oninput="calcCost('n')">
                    </div>
                    <div class="form-group">
                        <label>Purchase Price / Unit (KES) *</label>
                        <input type="number" name="unit_cost" id="n-cost" required min="0" step="0.01" placeholder="Price paid to supplier" oninput="calcCost('n')">
                    </div>
                </div>

                <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:6px;padding:10px 14px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--muted)">Total Cost to Supplier</span>
                    <span id="n-total" style="font-size:18px;font-weight:700;font-family:'DM Mono',monospace;color:var(--red)">KES 0.00</span>
                </div>

                <div class="form-group" style="margin-bottom:16px">
                    <label>Delivery Date *</label>
                    <input type="date" name="delivery_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Add Jersey & Record Delivery</button>
            </form>

        </div>
    </div>

    <!-- RECENT DELIVERIES -->
    <div class="card">
        <div class="card-header"><h3>Recent Deliveries</h3></div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Date</th><th>Jersey</th><th>Supplier</th><th>Qty</th><th>Unit Cost</th><th>Received By</th></tr></thead>
            <tbody>
            <?php if($deliveries->num_rows == 0): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">No deliveries yet</td></tr>
            <?php else: while($r = $deliveries->fetch_assoc()): ?>
                <tr>
                    <td style="font-family:'DM Mono',monospace;font-size:12px"><?php echo date('d M Y', strtotime($r['delivery_date'])); ?></td>
                    <td style="font-weight:600"><?php echo htmlspecialchars($r['team_name']); ?> <span style="color:var(--muted);font-weight:400"><?php echo $r['size']; ?></span></td>
                    <td style="font-size:12px"><?php echo htmlspecialchars($r['supplier_name']); ?></td>
                    <td><span class="badge badge-green">+<?php echo $r['quantity_received']; ?></span></td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px">KES <?php echo number_format($r['unit_cost'], 2); ?></td>
                    <td style="font-size:12px"><?php echo htmlspecialchars($r['full_name']); ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>

<script>
function switchMode(mode) {
    const isExisting = mode === 'existing';
    document.getElementById('form-existing').style.display = isExisting ? 'block' : 'none';
    document.getElementById('form-new').style.display      = isExisting ? 'none'  : 'block';
    document.getElementById('btn-existing').style.background = isExisting ? 'var(--green)' : '#f3f4f6';
    document.getElementById('btn-existing').style.color      = isExisting ? '#fff' : 'var(--muted)';
    document.getElementById('btn-new').style.background      = isExisting ? '#f3f4f6' : 'var(--green)';
    document.getElementById('btn-new').style.color           = isExisting ? 'var(--muted)' : '#fff';
}

function calcCost(prefix) {
    const qty  = parseFloat(document.getElementById(prefix + '-qty').value)  || 0;
    const cost = parseFloat(document.getElementById(prefix + '-cost').value) || 0;
    document.getElementById(prefix + '-total').textContent = 'KES ' + (qty * cost).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function previewPhoto(input) {
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('photo-preview');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('photo-label').textContent = input.files[0].name;
            document.getElementById('photo-drop').style.borderColor = 'var(--green)';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include("footer.php"); ?>
