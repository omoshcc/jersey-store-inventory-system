<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ['Manager','Storekeeper'])) {
    header("Location: dashboard.php"); exit();
}
include("header.php");
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
$msg = $_GET['msg'] ?? '';

// Create uploads folder if it doesn't exist
if(!is_dir("uploads")) mkdir("uploads", 0755, true);
?>
<div class="topbar">
    <div><h2>Add Jersey</h2><div class="sub">Add a new jersey to inventory</div></div>
    <a href="inventory.php" class="btn btn-secondary">← Back to Inventory</a>
</div>
<div class="page-content">
<?php if($msg == 'error'): ?><div class="alert alert-danger">Failed to add jersey. Please try again.</div><?php endif; ?>
<?php if($msg == 'imgfail'): ?><div class="alert alert-warning">Jersey added but photo upload failed. Check file type and size.</div><?php endif; ?>

<div class="card" style="max-width:620px">
    <div class="card-header"><h3>New Jersey Details</h3></div>
    <div class="card-body">
        <form action="add_inventory_process.php" method="POST" enctype="multipart/form-data">
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
                        <option value="A">A — High Value (Original jerseys)</option>
                        <option value="B" selected>B — Medium (Tracksuits, boots)</option>
                        <option value="C">C — Low Value (Accessories)</option>
                    </select>
                </div>
            </div>
            <div class="form-grid" style="margin-bottom:14px">
                <div class="form-group">
                    <label>Initial Quantity</label>
                    <input type="number" name="quantity" min="0" value="0" readonly style="background:#f3f4f6;color:var(--muted);cursor:not-allowed">
                    <span style="font-size:11px;color:var(--muted);margin-top:3px">Always starts at 0. Use <strong>Add Stock</strong> to bring in units.</span>
                </div>
                <div class="form-group">
                    <label>Reorder Point *</label>
                    <input type="number" name="reorder_point" required min="1" value="5">
                </div>
            </div>
            <div class="form-grid" style="margin-bottom:14px">
                <div class="form-group">
                    <label>Price per Jersey (KES) *</label>
                    <input type="number" name="price" required min="0" step="0.01" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id">
                        <option value="">— No supplier —</option>
                        <?php while($s = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- PHOTO UPLOAD -->
            <div class="form-group" style="margin-bottom:16px">
                <label>Jersey Photo (optional)</label>
                <div id="drop-zone" style="border:2px dashed var(--border);border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:border 0.2s;background:#fafafa" onclick="document.getElementById('photo-input').click()">
                    <svg width="32" height="32" fill="none" stroke="#9ca3af" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 8px auto;display:block"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <div id="drop-label" style="font-size:13px;color:var(--muted)">Click to upload jersey photo</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px">JPG, PNG or WEBP — max 5MB</div>
                    <img id="preview-img" src="" style="display:none;max-width:160px;max-height:160px;margin:12px auto 0;border-radius:6px;border:1px solid var(--border);object-fit:cover">
                </div>
                <input type="file" name="jersey_photo" id="photo-input" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewPhoto(this)">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Add to Inventory</button>
        </form>
    </div>
</div>
</div>

<script>
function previewPhoto(input) {
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('preview-img');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('drop-label').textContent = input.files[0].name;
            document.getElementById('drop-zone').style.borderColor = 'var(--green)';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php include("footer.php"); ?>
